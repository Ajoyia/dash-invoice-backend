<?php

namespace App\Http\Controllers;

use App\Services\Company\CompanyServiceInterface;
use App\Services\Company\VatValidationServiceInterface;
use App\Services\Company\CompanyLogoServiceInterface;
use App\Services\Company\CompanyExportServiceInterface;
use App\Services\Company\CompanyRegistrationMailServiceInterface;
use App\Http\Requests\CompanyRequest;
use App\Imports\CustomersImport;
use App\Utils\PermissionChecker;
use App\Http\Middleware\CheckPermissionHandler;
use App\Repositories\CompanyRepositoryInterface;
use App\Helpers\Helper;
use Illuminate\Support\Facades\App;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class CompanyController extends Controller implements HasMiddleware
{
    public function __construct(
        private CompanyServiceInterface $companyService,
        private VatValidationServiceInterface $vatValidationService,
        private CompanyLogoServiceInterface $logoService,
        private CompanyExportServiceInterface $exportService,
        private CompanyRegistrationMailServiceInterface $mailService,
        private CompanyRepositoryInterface $companyRepository
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermissionHandler::class . ':backoffice-company.create,company.create', only: ['store']),
            new Middleware(CheckPermissionHandler::class . ':backoffice-company.list,backoffice-company.show-all,company.show', only: ['show']),
            new Middleware(CheckPermissionHandler::class . ':backoffice-company.edit', only: ['update', 'sendRegisterMail']),
            new Middleware(CheckPermissionHandler::class . ':backoffice-company.delete', only: ['destroy']),
            new Middleware(CheckPermissionHandler::class . ':backoffice-company.import-csv', only: ['importCsv']),
            new Middleware(CheckPermissionHandler::class . ':backoffice-company-logo.upload', only: ['uploadCompanyLogo']),
            new Middleware(CheckPermissionHandler::class . ':backoffice-company-logo.delete', only: ['deleteCompanyLogo']),
            new Middleware(CheckPermissionHandler::class . ':backoffice.company.create-report', only: ['createReport']),
        ];
    }

    public function checkVatId(Request $request): JsonResponse
    {
        $vatNumber = $request->input('vatId');
        $result = $this->vatValidationService->validate($vatNumber);

        return response()->json($result);
    }

    public function index(Request $request): JsonResponse
    {
        $isAdmin = PermissionChecker::isAdmin($request) 
            || Helper::checkPermission('backoffice-company.show-all', $request);

        if (!$isAdmin && !Helper::checkPermission('backoffice-company.list', $request)) {
            return response()->json([
                'message' => 'You do not have enough permissions to access this functionality. Missing Permission:backoffice-company.show-all or backoffice-company.list'
            ], 403);
        }

        $companies = $this->companyService->getAllCompanies($request, $isAdmin);

        return response()->json([
            'data' => $companies->items(),
            'totalInvoiceSum' => collect($companies->items())->sum('invoiceSum'),
            'links' => $companies->links(),
            'current_page' => $companies->currentPage(),
            'from' => $companies->firstItem(),
            'last_page' => $companies->lastPage(),
            'path' => $request->url(),
            'per_page' => $companies->perPage(),
            'to' => $companies->lastItem(),
            'total' => $companies->total(),
        ], 200);
    }

    public function store(CompanyRequest $request): JsonResponse
    {
        try {
            $company = $this->companyService->createCompany($request->all(), $request);

            return response()->json([
                'success' => true,
                'message' => "Customer has been created",
                'data' => $company
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $result = $this->companyService->getCompanyById($id, $request);
        return response()->json($result);
    }

    public function update(CompanyRequest $request, string $id): JsonResponse
    {
        try {
            $this->companyService->updateCompany($id, $request->all(), $request);

            return response()->json([
                'success' => true,
                'message' => "Customer has been updated",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function createReport(): JsonResponse
    {
        $companies = $this->companyRepository->getAll();
        return $this->exportService->exportToCsv($companies, 'companies_report.csv');
    }

    public function sendRegisterMail(Request $request): JsonResponse
    {
        try {
            $this->mailService->sendRegistrationMail($request->all());

            return response()->json(['message' => "Mail sent successfully"]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->companyService->deleteCompany($id);

            return response()->json(['message' => 'Record deleted.'], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function restore(string $id): JsonResponse
    {
        try {
            $this->companyService->restoreCompany($id);

            return response()->json(['message' => 'Record restored.'], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function getCredits(Request $request): JsonResponse
    {
        $companyId = Helper::getCompanyId($request->bearerToken());
        $credits = $this->companyService->getCompanyCredits($companyId);

        return response()->json(['credits' => $credits]);
    }

    public function getResellerPartner(Request $request): JsonResponse
    {
        return response()->json([
            'salesPartnerCompany' => null,
            'resellerCompany' => null,
            'servicePartnerCompany' => null,
        ]);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required']);

        $locale = $request->header('Accept-Language', 'en');
        App::setLocale($locale);

        try {
            Excel::import(new CustomersImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => "CSV imported successfully"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.csv_upload_error'),
            ], 422);
        }
    }

    public function uploadCompanyLogo(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required']);

        try {
            $companyId = Helper::getCompanyId($request->bearerToken());
            $company = $this->companyRepository->find($companyId);

            if (!$company) {
                return response()->json(['message' => 'Company not found'], 400);
            }

            $this->logoService->uploadLogo($company, $request->image);

            return response()->json([
                'success' => true,
                'message' => "Logo uploaded successfully"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deleteCompanyLogo(Request $request): JsonResponse
    {
        try {
            $companyId = Helper::getCompanyId($request->bearerToken());
            $company = $this->companyRepository->find($companyId);

            if (!$company) {
                return response()->json(['message' => 'Company not found'], 400);
            }

            $this->logoService->deleteLogo($company);

            return response()->json([
                'success' => true,
                'message' => "Logo deleted successfully"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}