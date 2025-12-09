<?php

namespace App\Services\Company;

use App\Exceptions\CompanyNotFoundException;
use App\Helpers\Helper;
use App\Models\Company;
use App\Repositories\CompanyRepositoryInterface;
use App\Utils\PermissionChecker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyService implements CompanyServiceInterface
{
    public function __construct(
        private CompanyRepositoryInterface $repository,
        private CompanyDataTransformer $transformer
    ) {}

    public function getAllCompanies(Request $request, bool $isAdmin): LengthAwarePaginator
    {
        $companyId = $request->get('companyId') ?: Helper::getCompanyId($request->bearerToken());
        $perPage = $request->perPage ?? 25;
        $sortBy = $request->get('sortBy');
        $sortOrder = $request->get('sortOrder');

        $filters = $request->only('search', 'status');

        $filterCompanyId = $isAdmin ? null : $companyId;
        $companies = $this->repository->getCompaniesWithInvoices($filters, $perPage, $sortBy, $sortOrder, $filterCompanyId);

        $transformedData = $companies->getCollection()->map(function ($company) {
            return $this->transformer->transformForList($company);
        })->unique('id')->values();

        $companies->setCollection($transformedData);

        return $companies;
    }

    public function getCompanyById(string $id, Request $request): array
    {
        $company = $this->repository->find($id);

        if ($company === null) {
            return ['modelData' => []];
        }

        return [
            'modelData' => $this->transformer->transformForDetail($company),
        ];
    }

    public function createCompany(array $data, Request $request): Company
    {
        return DB::transaction(function () use ($data, $request) {
            $companyData = $this->prepareCompanyData($data, $request);
            $company = $this->repository->create($companyData);

            if (!empty($data['bankDetails'])) {
                $this->createBankDetails($company, $data['bankDetails']);
            }

            return $company;
        });
    }

    public function updateCompany(string $id, array $data, Request $request): Company
    {
        $company = $this->repository->findOrFail($id);

        return DB::transaction(function () use ($company, $data, $request) {
            $companyData = $this->prepareUpdateData($data, $request);
            $this->repository->update($company, $companyData);

            if (!empty($data['bankDetails'])) {
                $this->updateBankDetails($company, $data['bankDetails']);
            }

            return $company->fresh();
        });
    }

    public function deleteCompany(string $id): bool
    {
        $company = $this->repository->findOrFail($id);

        return $this->repository->delete($company);
    }

    public function restoreCompany(string $id): bool
    {
        $company = $this->repository->find($id);

        if ($company === null) {
            throw new CompanyNotFoundException;
        }

        return $this->repository->restore($company);
    }

    public function getCompanyCredits(string $companyId): float
    {
        $company = $this->repository->find($companyId);

        if ($company === null) {
            throw new CompanyNotFoundException;
        }

        return (float) ($company->credits ?? 0.0);
    }

    private function prepareCompanyData(array $data, Request $request): array
    {
        $companyData = [
            'company_name' => $data['companyName'],
            'vat_id' => $data['vatId'] ?? null,
            'address_line_1' => $data['addressLine1'],
            'address_line_2' => $data['addressLine2'] ?? null,
            'city' => $data['city'],
            'country' => $data['country'],
            'zip_code' => $data['zipCode'],
            'phone' => $data['phone'] ?? null,
            'contact_language' => $data['contactLanguage'] ?? null,
            'status' => $data['status'] ?? 'new',
            'apply_reverse_charge' => $data['applyReverseCharge'] ?? false,
            'external_order_number' => $data['externalOrderNumber'] ?? null,
            'warning_invoice_email' => $data['warningMailAddress'] ?? null,
            'notification_mail' => $data['notificationMail'] ?? null,
            'free_cases_count' => $data['freeCases'] ?? null,
            'company_number' => $this->repository->generateCompanyNumber(),
        ];

        if ($this->canEditCredits($request)) {
            $companyData['credits'] = $data['credits'] ?? 0;
        }

        return $companyData;
    }

    private function prepareUpdateData(array $data, Request $request): array
    {
        $companyData = [];

        $fieldMap = [
            'companyName' => 'company_name',
            'vatId' => 'vat_id',
            'addressLine1' => 'address_line_1',
            'addressLine2' => 'address_line_2',
            'city' => 'city',
            'country' => 'country',
            'zipCode' => 'zip_code',
            'phone' => 'phone',
            'contactLanguage' => 'contact_language',
            'status' => 'status',
            'applyReverseCharge' => 'apply_reverse_charge',
            'externalOrderNumber' => 'external_order_number',
            'warningMailAddress' => 'warning_invoice_email',
            'notificationMail' => 'notification_mail',
            'freeCases' => 'free_cases_count',
            'invoiceEmailAddress' => 'invoice_email',
        ];

        foreach ($fieldMap as $requestKey => $dbKey) {
            if (isset($data[$requestKey])) {
                $companyData[$dbKey] = $data[$requestKey];
            }
        }

        if ($this->canEditCredits($request) && isset($data['credits'])) {
            $company = $this->repository->find($data['id'] ?? '');
            if ($company) {
                $companyData['credits'] = ($company->credits ?? 0) + $data['credits'];
            }
        }

        return $companyData;
    }

    private function createBankDetails(Company $company, array $bankDetails): void
    {
        $banksData = array_map(function ($detail) {
            return [
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
                'country_name' => is_array($detail['country'] ?? null)
                    ? ($detail['country']['name'] ?? null)
                    : ($detail['country'] ?? null),
            ];
        }, $bankDetails);

        $company->bankDetails()->createMany($banksData);
    }

    private function updateBankDetails(Company $company, array $bankDetails): void
    {
        $company->bankDetails()->delete();
        $this->createBankDetails($company, $bankDetails);
    }

    private function canEditCredits(Request $request): bool
    {
        return PermissionChecker::isAdmin($request)
            || Helper::checkPermission('backoffice-companies-coins.edit', $request);
    }
}
