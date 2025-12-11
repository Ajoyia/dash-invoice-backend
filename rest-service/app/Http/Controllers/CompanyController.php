<?php

namespace App\Http\Controllers;

use App\Exceptions\CompanyNotFoundException;
use App\Helpers\Helper;
use App\Http\Middleware\CheckPermissionHandler;
use App\Http\Requests\CompanyRequest;
use App\Imports\CustomersImport;
use App\Repositories\CompanyRepositoryInterface;
use App\Services\Company\CompanyExportServiceInterface;
use App\Services\Company\CompanyLogoServiceInterface;
use App\Services\Company\CompanyRegistrationMailServiceInterface;
use App\Services\Company\CompanyServiceInterface;
use App\Services\Company\VatValidationServiceInterface;
use App\Utils\PermissionChecker;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            new Middleware(CheckPermissionHandler::class.':backoffice-company.create,company.create', only: ['store']),
            new Middleware(CheckPermissionHandler::class.':backoffice-company.list,backoffice-company.show-all,company.show', only: ['show']),
            new Middleware(CheckPermissionHandler::class.':backoffice-company.edit', only: ['update', 'sendRegisterMail']),
            new Middleware(CheckPermissionHandler::class.':backoffice-company.delete', only: ['destroy']),
            new Middleware(CheckPermissionHandler::class.':backoffice-company.import-csv', only: ['importCsv']),
            new Middleware(CheckPermissionHandler::class.':backoffice-company-logo.upload', only: ['uploadCompanyLogo']),
            new Middleware(CheckPermissionHandler::class.':backoffice-company-logo.delete', only: ['deleteCompanyLogo']),
            new Middleware(CheckPermissionHandler::class.':backoffice.company.create-report', only: ['createReport']),
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/check-vat-id",
     *     summary="Validate VAT ID",
     *     description="Check if a VAT ID is valid",
     *     operationId="CheckVatId",
     *     tags={"Companies"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="vatId", type="string", description="VAT ID to validate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     )
     * )
     */
    public function checkVatId(Request $request): JsonResponse
    {
        $vatNumber = $request->input('vatId');
        $result = $this->vatValidationService->validate($vatNumber);

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/api/companies",
     *     summary="Get companies list",
     *     description="Retrieve paginated list of companies",
     *     operationId="CompaniesList",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *          name="perPage",
     *          description="Records per page (default 25)",
     *          in="query",
     *          @OA\Schema(type="integer"),
     *      ),
     *     @OA\Parameter(
     *          name="sortBy",
     *          description="Sort by column",
     *          in="query",
     *          @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="sortOrder",
     *          description="Sort order (asc, desc)",
     *          in="query",
     *          @OA\Schema(type="string"),
     *      ),
     *     @OA\Parameter(
     *         name="search",
     *          description="Search in different columns",
     *          in="query",
     *          @OA\Schema(type="string"),
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $isAdmin = PermissionChecker::isAdmin($request)
            || Helper::checkPermission('backoffice-company.show-all', $request);

        if (! $isAdmin && ! Helper::checkPermission('backoffice-company.list', $request)) {
            return response()->json([
                'message' => 'You do not have enough permissions to access this functionality. Missing Permission:backoffice-company.show-all or backoffice-company.list',
            ], 403);
        }

        $companies = $this->companyService->getAllCompanies($request, $isAdmin);

        return response()->json([
            'data' => $companies->items(),
            'totalInvoiceSum' => collect($companies->items())->sum('invoiceSum'),
            'current_page' => $companies->currentPage(),
            'from' => $companies->firstItem(),
            'last_page' => $companies->lastPage(),
            'path' => $request->url(),
            'per_page' => $companies->perPage(),
            'to' => $companies->lastItem(),
            'total' => $companies->total(),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     summary="Create a new company",
     *     description="Create a new company record",
     *     operationId="CreateCompany",
     *     tags={"Companies"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="companyName", type="string", description="Company name"),
     *             @OA\Property(property="vatId", type="string", description="VAT ID"),
     *             @OA\Property(property="email", type="string", description="Email address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company created successfully",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *     )
     * )
     */
    public function store(CompanyRequest $request): JsonResponse
    {
        try {
            $company = $this->companyService->createCompany($request->all(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Customer has been created',
                'data' => $company,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company. Please try again.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/companies/{id}",
     *      operationId="getCompanyById",
     *      tags={"Companies"},
     *      summary="Get single company record",
     *      description="Returns single company",
     *      @OA\Parameter(
     *          name="id",
     *          description="Company ID",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="string"),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Company not found",
     *      )
     *     )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $result = $this->companyService->getCompanyById($id, $request);

        return response()->json($result);
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{id}",
     *     operationId="UpdateCompany",
     *     tags={"Companies"},
     *     summary="Update an existing company",
     *     description="Update company details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="companyName", type="string", description="Company name"),
     *             @OA\Property(property="vatId", type="string", description="VAT ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated successfully",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *     )
     * )
     */
    public function update(CompanyRequest $request, string $id): JsonResponse
    {
        try {
            $this->companyService->updateCompany($id, $request->all(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Customer has been updated',
            ]);
        } catch (CompanyNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company. Please try again.',
            ], 500);
        }
    }

    public function createReport(): StreamedResponse
    {
        $companies = $this->companyRepository->getAll();

        return $this->exportService->exportToCsv($companies, 'companies_report.csv');
    }

    public function sendRegisterMail(Request $request): JsonResponse
    {
        try {
            $this->mailService->sendRegistrationMail($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Mail sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send mail. Please try again.',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{id}",
     *     operationId="DeleteCompany",
     *     tags={"Companies"},
     *     summary="Delete a company",
     *     description="Delete a company by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company deleted successfully",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->companyService->deleteCompany($id);

            return response()->json([
                'success' => true,
                'message' => 'Record deleted.',
            ]);
        } catch (CompanyNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function restore(string $id): JsonResponse
    {
        try {
            $this->companyService->restoreCompany($id);

            return response()->json([
                'success' => true,
                'message' => 'Record restored.',
            ]);
        } catch (CompanyNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function getCredits(Request $request): JsonResponse
    {
        $companyId = Helper::getCompanyId($request->bearerToken());
        if ($companyId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        $credits = $this->companyService->getCompanyCredits($companyId);

        return response()->json([
            'success' => true,
            'data' => ['credits' => $credits],
        ]);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $locale = $request->header('Accept-Language', 'en');
        App::setLocale($locale);

        try {
            Excel::import(new CustomersImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'CSV imported successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.csv_upload_error'),
            ], 422);
        }
    }

    public function uploadCompanyLogo(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);

        try {
            $companyId = Helper::getCompanyId($request->bearerToken());
            if ($companyId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                ], 401);
            }

            $company = $this->companyRepository->find($companyId);
            if ($company === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $this->logoService->uploadLogo($company, $request->image);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo. Please try again.',
            ], 500);
        }
    }

    public function deleteCompanyLogo(Request $request): JsonResponse
    {
        try {
            $companyId = Helper::getCompanyId($request->bearerToken());
            if ($companyId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                ], 401);
            }

            $company = $this->companyRepository->find($companyId);
            if ($company === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $this->logoService->deleteLogo($company);

            return response()->json([
                'success' => true,
                'message' => 'Logo deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete logo. Please try again.',
            ], 500);
        }
    }
}
