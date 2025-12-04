<?php

namespace App\Helpers;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as staticRequest;
use App\Traits\CustomHelper;
use App\Utils\PermissionChecker;
use Carbon\Carbon;
use App\Helpers\Helper;
use Exception;

class CustomerHelper
{
    use CustomHelper;

    public function index($request, $isAdmin)
    {
        if ($request->get('companyId')) {
            $company_id = $request->get('companyId');
        } else {
            $company_id = Helper::getCompanyId($request->bearerToken());
        }
        $per_page = $request->perPage ?? 25;
        $sort_by = $request->get('sortBy');
        $sort_order = $request->get('sortOrder');

        $query = Company::query();

        if ($sort_by && $sort_order && $sort_by != 'createdAt') {
            $query = $this->applySortingBeforePagination($query, $sort_by, $sort_order);
        }

        $query->when(
            !$isAdmin,
            function ($q) use ($company_id) {
                $q->where('id', $company_id);
            }
        );

        if ($sort_by == 'createdAt') {
            $models = $query->with('invoices')
                ->filter(staticRequest::only('search'))
                ->orderBy('companies.created_at', $sort_order)
                ->paginate($per_page);
        } else {
            $models = $query->with('invoices')
                ->filter(staticRequest::only('search'))
                ->paginate($per_page);
        }

        $model_collect = $models->getCollection()->map(function ($item) {
            try {
                $invoiceSum = $item->invoices()->sum('total_amount');
            } catch (\Exception $e) {
                $invoiceSum = collect($item->invoices)->sum('total_amount');
            }

            return [
                'id' => $item->id,
                'companyNumber' => $item->company_number,
                'companyName' => $item->company_name,
                'addressLine1' => $item->address_line_1,
                'vatId' => $item->vat_id,
                'zipCode' => $item->zip_code,
                'preferredContactLanguage' => $item->contact_language,
                'city' => $item->city,
                'credits' => $item->credits,
                'status' => $item->status,
                'invoiceSum' => $invoiceSum,
                'country' => $item->country,
                'applyReverseCharge' => $item->apply_reverse_charge,
                'externalOrderNumber' => $item->external_order_number ?? null,
                'phone' => $item->phone ?? "",
                'invoiceEmailAddress' => $item->invoice_email ?? "",
                'warningMailAddress' => $item->warning_invoice_email ?? "",
                'createdAt' => Carbon::parse($item->created_at)->format('Y-m-d'),
            ];
        })->unique('id')->values();
        $models->setCollection($model_collect);
        return response()->json([
            'data' => $model_collect,
            'totalInvoiceSum' =>  $model_collect->sum('invoiceSum'),
            'links' => $models->links(),
            'current_page' => $models->currentPage(),
            'from' => $models->firstItem(),
            'last_page' => $models->lastPage(),
            'path' => $request->url(),
            'per_page' => $models->perPage(),
            'to' => $models->lastItem(),
            'total' => $models->total(),
        ], 200);
    }

    public function getResellersPartners($request)
    {
        $per_page = $request->perPage ?? 25;

        $query = Company::query();

        $models = $query
            ->filter(staticRequest::only('search'))
            ->orderBy('company_name', 'asc')
            ->paginate($per_page);

        $model_collect = $models->getCollection()->map(function ($model) {
            return [
                'id' => $model->id,
                'companyNumber' => $model->company_number,
                'companyName' => $model->company_name,
            ];
        });

        $models->setCollection($model_collect);
        return response()->json([
            'data' => $model_collect,
            'links' => $models->links(),
            'current_page' => $models->currentPage(),
            'from' => $models->firstItem(),
            'last_page' => $models->lastPage(),
            'path' => $request->url(),
            'per_page' => $models->perPage(),
            'to' => $models->lastItem(),
            'total' => $models->total(),
        ], 200);
    }

    public function store($request)
    {
        try {
            $company = null;
            DB::transaction(function () use ($request, &$company) {
                $company_id = Helper::getCompanyId($request->bearerToken());

                $company = new Company;
                $company->company_name = $request->companyName;
                $company->vat_id = $request->vatId;
                $company->address_line_1 = $request->addressLine1;
                $company->status = 'new';
                $company->address_line_2 = $request->addressLine2;
                $company->city = $request->city;
                $company->status = $request->status;
                if (PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-dentaltwin-coins.edit', $request))
                    $company->credits = $request->credits;
                $company->country = $request->country;
                $company->zip_code = $request->zipCode;
                $company->phone = $request->phone;
                $company->contact_language = $request->contactLanguage;
                $company->free_cases_count = $request->freeCases;
                $company->apply_reverse_charge = $request->applyReverseCharge ?? 0;
                $company->external_order_number = $request->externalOrderNumber ?? 0;
                $company->warning_invoice_email = $request->warningMailAddress ?? null;
                $company->notification_mail = $request->notificationMail ?? null;

                $companyNumber = DB::table('companies')->max(DB::raw("CAST(SUBSTRING(company_number, 2) AS UNSIGNED)")) ?? 1000;
                $company->company_number = 'C' . ($companyNumber + 1);
                $company->save();

                if (!empty($request->bankDetails)) {
                    $banks_data = [];

                    foreach ($request->bankDetails as $detail) {
                        $banks_data[] = [
                            'bank_name' => $detail['bankName'] ?? null,
                            'swift' => $detail['swift'] ?? null,
                            'iban' => $detail['iban'] ?? null,
                            'routing_number' => $detail['routing_number'] ?? null,
                            'account_number' => $detail['account_number'] ?? null,
                            'institution_number' => $detail['institution_number'] ?? null,
                            'transit_number' => $detail['transit_number'] ?? null,
                            'bsb_code' => $detail['bsb_code'] ?? null,
                            'branch_code' => $detail['branch_code'] ?? null,
                            'bank_code' => $detail['bank_code'] ?? null,
                            'country_name' => is_array($detail['country'] ?? null) ? $detail['country']['name'] : ($detail['country'] ?? null),
                        ];
                    }

                    $company->bankDetails()->createMany($banks_data);
                }
            });
            return response()->json([
                'success' => true,
                'message' => "Customer has been created",
                "data" => $company
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getResellerPartner($request)
    {
        $company_id = Helper::getCompanyId($request->bearerToken());
        $company = Company::find($company_id);

        if ($company) {
            return response()->json([
                'salesPartnerCompany' => null,
                'resellerCompany' => null,
                'servicePartnerCompany' => null,
            ]);
        }

        return response()->json([
            'salesPartner' => null,
            'resellerCompany' => null
        ]);
    }

    public function show($request, $id)
    {
        $model = Company::where('id', $id)->first();
        if (!empty($model)) {
            $company_id = Helper::getCompanyId($request->bearerToken());

            return response()->json([
                'modelData' => [
                    'id' => $model->id,
                    'companyName' => $model->company_name,
                    'vatId' => $model->vat_id,
                    'addressLine1' => $model->address_line_1,
                    'addressLine2' => $model->address_line_2,
                    'city' => $model->city,
                    'credits' => $model->credits,
                    'country' => $model->country,
                    'zipCode' => $model->zip_code,
                    'preferredContactLanguage' => $model->contact_language,
                    'phone' => $model->phone,
                    'status' => $model->status,
                    'invoiceEmailAddress' => $model->invoice_email,
                    'applyReverseCharge' => $model->apply_reverse_charge,
                    'freeCases' => $model->free_cases_count,
                    'externalOrderNumber' => $model->external_order_number,
                    'warningMailAddress' => $model->warning_invoice_email ?? '',
                    'notificationMail' => $model->notification_mail ?? '',
                    'bankDetails' => isset($model->bankDetails) ? $model->bankDetails->map(function ($bank_detail) {
                        return [
                            'bankName' => $bank_detail->bank_name,
                            'swift' => $bank_detail->swift,
                            'iban' => $bank_detail->iban,
                            'routing_number' => $bank_detail->routing_number,
                            'account_number' => $bank_detail->account_number,
                            'institution_number' => $bank_detail->institution_number,
                            'transit_number' => $bank_detail->transit_number,
                            'bsb_code' => $bank_detail->bsb_code,
                            'branch_code' => $bank_detail->branch_code,
                            'bank_code' => $bank_detail->bank_code,
                            'country_name' => $bank_detail->country_name
                        ];
                    }) : [],
                ],
            ]);
        }
        return response()->json([
            'modelData' => []
        ]);
    }

    public function update($request, $id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        try {
            DB::transaction(function () use ($request, &$company) {
                if (isset($request->companyName)) {
                    $company->company_name = $request->companyName;
                }
                if (isset($request->vatId)) {
                    $company->vat_id = $request->vatId;
                }
                if (isset($request->addressLine1)) {
                    $company->address_line_1 = $request->addressLine1;
                }
                if (isset($request->addressLine2)) {
                    $company->address_line_2 = $request->addressLine2;
                }
                if (isset($request->city)) {
                    $company->city = $request->city;
                }
                if (isset($request->country)) {
                    $company->country = $request->country;
                }
                if (isset($request->zipCode)) {
                    $company->zip_code = $request->zipCode;
                }
                if (isset($request->status)) {
                    $company->status = $request->status;
                }
                if (isset($request->phone)) {
                    $company->phone = $request->phone;
                }
                if (isset($request->contactLanguage)) {
                    $company->contact_language = $request->contactLanguage;
                }
                if (isset($request->freeCases)) {
                    $company->free_cases_count = $request->freeCases;
                }
                if (isset($request->invoiceEmailAddress)) {
                    $company->invoice_email = $request->invoiceEmailAddress;
                }
                if ((PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-dentaltwin-coins.edit', $request))
                    &&
                    isset($request->credits)
                ) {
                    $company->credits = $company->credits + $request->credits;
                }
                if (isset($request->status)) {
                    $company->status = $request->status;
                }
                if (isset($request->applyReverseCharge)) {
                    $company->apply_reverse_charge = $request->applyReverseCharge;
                }
                if (isset($request->externalOrderNumber)) {
                    $company->external_order_number = $request->externalOrderNumber;
                }
                if (isset($request->warningMailAddress)) {
                    $company->warning_invoice_email = $request->warningMailAddress;
                }
                if (isset($request->notificationMail)) {
                    $company->notification_mail = $request->notificationMail;
                }

                $company->save();

                if (!empty($request->bankDetails)) {
                    if (isset($company->bankDetails)) {
                        $company->bankDetails()->delete();
                    }

                    $banks_data = [];
                    foreach ($request->bankDetails as $detail) {
                        $banks_data[] = [
                            'bank_name'        => $detail['bankName'] ?? null,
                            'swift'            => $detail['swift'] ?? null,
                            'iban'             => $detail['iban'] ?? null,
                            'routing_number'   => $detail['routing_number'] ?? null,
                            'account_number'   => $detail['account_number'] ?? null,
                            'institution_number' => $detail['institution_number'] ?? null,
                            'transit_number'   => $detail['transit_number'] ?? null,
                            'bsb_code'         => $detail['bsb_code'] ?? null,
                            'branch_code'      => $detail['branch_code'] ?? null,
                            'bank_code'        => $detail['bank_code'] ?? null,
                            'country_name'     => is_array($detail['country'] ?? null)
                                ? $detail['country']['name']
                                : ($detail['country'] ?? null),
                        ];
                    }
                    $company->bankDetails()->createMany($banks_data);
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Customer has been updated",
        ]);
    }

    public function createCSV($companies, $file_name)
    {
        $columns = [
            'Company Number',
            'Company Name',
            'Creation Date',
            'Status',
        ];

        $columnMap = [
            'Company Number'        => fn($company) => $company->company_number ?? '',
            'Company Name'          => fn($company) => $company->company_name,
            'Creation Date'         => fn($company) => $this->formatDate($company->created_at, 'de', 'EUR'),
            'Status'                => fn($company) => $company->status,
        ];

        $callback = function () use ($companies, $columns, $columnMap) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $columns);

            foreach ($companies as $company) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $columnMap[$column]($company);
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $file_name, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$file_name\""
        ]);
    }

    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Company not found.'], 404);
        }

        $company->delete();

        return response()->json(['message' => 'Record deleted.'], 200);
    }
}
