<?php

namespace App\Helpers;

use App\Models\UploadedFile;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Helper
{
    public static function getCompanyId(string $token): ?string
    {
        try {
            $tokenResponse = (array) JWT::decode($token, new Key(
                config('session.JWT_KEY'),
                'HS256'
            ));

            return $tokenResponse['company_id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function isForeignKey(string $table, string $column): bool
    {
        $schemaName = config('database.connections.mysql.database');
        $foreignKeys = DB::select('
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_COLUMN_NAME IS NOT NULL', [$schemaName, $table, $column]);

        return !empty($foreignKeys);
    }

    public static function checkPermission(string $permission, Request $request): bool
    {
        $userPermissions = $request->get('auth_user_permissions') ?? [];

        return in_array($permission, $userPermissions, true);
    }

    public static function toCamelCase(string $string): string
    {
        $string = strtolower(str_replace(' ', '_', $string));

        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string), ' ')));
    }

    public static function saveAttachment(array $file, Model $model, ?string $type = null): void
    {
        $originalName = $file['name'] ?? '';
        $extension = $file['extension'] ?? '';
        $id = $file['id'] ?? '';

        $uploadedFile = new UploadedFile();
        if ($type !== null) {
            $uploadedFile->type = $type;
        }
        $uploadedFile->storage_name = $id;
        $uploadedFile->viewable_name = $originalName;
        $uploadedFile->storage_size = $extension;
        $uploadedFile->fileable()->associate($model);
        $uploadedFile->save();
    }

    public static function removeAttachment(Model $model, ?string $type = null): void
    {
        $query = UploadedFile::where('fileable_id', $model->id)
            ->where('fileable_type', get_class($model));

        if ($type !== null) {
            $query->where('type', $type);
        }

        $uploadedFile = $query->first();

        if ($uploadedFile !== null) {
            $uploadedFile->delete();
        }
    }
}
