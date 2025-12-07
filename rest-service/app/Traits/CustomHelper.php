<?php

namespace App\Traits;

use App\Models\CaseLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use NumberFormatter;
use App\Models\MailTemplateAssignment;
use DateTime;
use Illuminate\Support\Facades\Http;
use Exception;

trait CustomHelper
{

    public function applySortingBeforePagination($query, string $sortBy, string $sortOrder)
    {
        $sortByParts = explode('.', $sortBy);

        if (count($sortByParts) > 1) {
            // Handle nested relationship sorting
            $nestedColumn = Str::snake(array_pop($sortByParts));
            $nestedRelationship = implode('.', $sortByParts);
            $relatedModel = "App\Models\\" . Str::studly($nestedRelationship);

            $modelInstance = app($relatedModel);
            $foreignKeyName = $query->getModel()->{$nestedRelationship}()->getForeignKeyName();
            return $query->leftJoin(
                $modelInstance->getTable(),
                "{$query->getModel()->getTable()}.{$foreignKeyName}",
                '=',
                "{$modelInstance->getTable()}.id"
            )
                ->select("{$modelInstance->getTable()}.*", "{$query->getModel()->getTable()}.*")
                ->orderBy("{$modelInstance->getTable()}.{$nestedColumn}", $sortOrder);
        }

        $sortByParts = explode('-', $sortBy);
        $sortByUnderscore = explode('_', $sortBy);

        if (count($sortByParts) > 1) {
            // Handle date range sorting
            $startColumn = Str::snake($sortByParts[0]);
            $endColumn = Str::snake($sortByParts[1]);

            return $query->orderBy($startColumn, $sortOrder)
                ->orderBy($endColumn, $sortOrder);
        }

        if (count($sortByUnderscore) > 2) {
            // Handle three-way nested relationship sorting
            [$firstRelation, $secondRelation, $nestedColumn] = [
                $sortByUnderscore[0],
                $sortByUnderscore[1],
                Str::snake($sortByUnderscore[2])
            ];

            $firstModel = "App\Models\\" . Str::studly($firstRelation);
            $secondModel = "App\Models\\" . Str::studly($secondRelation);

            $firstInstance = app($firstModel);
            $secondInstance = app($secondModel);

            $firstForeignKey = $query->getModel()->{$firstRelation}()->getForeignKeyName();
            $secondForeignKey = $firstInstance->{$secondRelation}()->getForeignKeyName();

            return $query->leftJoin(
                $firstInstance->getTable(),
                "{$query->getModel()->getTable()}.{$firstForeignKey}",
                '=',
                "{$firstInstance->getTable()}.id"
            )
                ->leftJoin(
                    $secondInstance->getTable(),
                    "{$secondInstance->getTable()}.{$secondForeignKey}",
                    '=',
                    "{$firstInstance->getTable()}.id"
                )
                ->select(
                    "{$secondInstance->getTable()}.*",
                    "{$firstInstance->getTable()}.*",
                    "{$query->getModel()->getTable()}.*"
                )
                ->orderBy("{$secondInstance->getTable()}.{$nestedColumn}", $sortOrder);
        }

        // Simple column sorting
        $columnName = Str::snake($sortByParts[0]);
        return str_contains($columnName, '_numeric')
            ? $query->orderByRaw("CAST(" . str_replace('_numeric', '', $columnName) . " AS SIGNED) {$sortOrder}")
            : $query->orderBy($columnName, $sortOrder);
    }

    public function convertKeysToSnakeCase(Collection $collection): array
    {
        $collection = $collection->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();
        return $collection;
    }

    public function sendMail($request, $data, $user_ids, $module, $notification_mail = null, $isSendNotificationMail = false)
    {
        $mails = [];
        
        if ($user_ids) {
            $token = $request->bearerToken();
            $url = config('dashinvoice.AUTH_SERVICE_URL');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($url . '/list-user-by-id?' . http_build_query(['id' => $user_ids->toArray()]));

            $response = $response->json();
            $users = collect($response ?? []);
            $mails = $users->pluck('email')->filter()->values()->all();
        }
        // Retrieve mail template for module "userActivateTemplate"
        $mailTemplate = MailTemplateAssignment::where('module', $module)->first();
        $from_mail = $mailTemplate->sender_mail ?? null;

        if ($isSendNotificationMail) {
            if (!empty($notification_mail)) {
                $mails[] = $notification_mail;
                $mails = array_unique($mails);
            }
        }

        $arr = [
            "id" => $mailTemplate->mail_template_id ?? null,
            "data" => $data,
            "cc" => $mailTemplate->cc ?? null,
            "bcc" => $mailTemplate->bcc ?? null,
            "mails" => $mails,
        ];


        // Validate 'cc' if provided. If it's a string, split it by whitespace, commas, or semicolons.
        if (isset($arr['cc'])) {
            if (is_string($arr['cc'])) {
                $arr['cc'] = preg_split('/[\s,;]+/', $arr['cc'], -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        // Validate 'bcc' if provided. If it's a string, split it by whitespace, commas, or semicolons.
        if (isset($arr['bcc'])) {
            if (is_string($arr['bcc'])) {
                $arr['bcc'] = preg_split('/[\s,;]+/', $arr['bcc'], -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        // Manually create a Redis instance using configuration.
        $redis = new \Redis();
        // You can use configuration values or environment variables.
        $host = config('authredis.connection.host') ?: env('REDIS_HOST', '127.0.0.1');
        $port = config('authredis.connection.port') ?: env('REDIS_PORT', 6379);
        $password = config('authredis.connection.password') ?: env('REDIS_PASSWORD', null);
        $redis->connect($host, $port);
        if ($password) {
            $redis->auth($password);
        }
        $redis->lPush($from_mail . '_mail_queue', json_encode($arr));
    }

    public function formatNumber(
        $number,
        $language = 'en',
        $currency = 'EUR',
        $simpleNumber = false,
        $minimumFractionDigits = 2,
        $maximumFractionDigits = 20
    ) {
        $languages = [
            'en' => 'GB',
            'de' => 'DE',
        ];

        // Ensure locale fallback works properly
        $locale = isset($languages[$language]) ? $language . '-' . $languages[$language] : 'en-GB';

        $formattedNumber = $number;

        try {
            $formatter = new NumberFormatter($locale, $simpleNumber ? NumberFormatter::DECIMAL : NumberFormatter::CURRENCY);

            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $minimumFractionDigits);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maximumFractionDigits);

            if (!$simpleNumber) {
                $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency);
            }

            $formattedNumber = $formatter->format($number);
            $formattedNumber = mb_convert_encoding($formattedNumber, 'UTF-8', 'auto');
        } finally {
            return $formattedNumber;
        }
    }

    function formatDate($date, $language = 'en')
    {
        try {
            // Convert the date string to a DateTime object
            $dateTemp = new DateTime($date);

            // Format the date and time
            $dateString = $dateTemp->format($language === 'de' ? 'd.m.Y' : 'm/d/Y');
            $timeString = $dateTemp->format('H:i:s');

            // Return formatted date with or without time
            return strpos($date, ':') !== false
                ? $dateString . ' ' . $timeString
                : $dateString;
        } catch (Exception $e) {
            return '';
        }
    }
}