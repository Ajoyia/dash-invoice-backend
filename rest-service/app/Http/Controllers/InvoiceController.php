<?php

namespace App\Http\Controllers;

use App\Exports\InvoicePlanExport;
use App\Exceptions\InvoiceNotFoundException;
use App\Helpers\Helper;
use App\Http\Middleware\CheckPermissionHandler;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService\InvoiceService;
use App\Traits\CustomHelper;
use App\Utils\PermissionChecker;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller implements HasMiddleware
{
    use CustomHelper;

    protected $invoiceService;

    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermissionHandler::class.':invoice.list', only: ['customerInvoices']),
            new Middleware(CheckPermissionHandler::class.':invoice.export-csv', only: ['downloadCustomerCSV']),
            new Middleware(CheckPermissionHandler::class.':invoice.export-csv-latest', only: ['downloadLatestCustomerCSV']),
            new Middleware(CheckPermissionHandler::class.':invoice.list,backoffice-invoice.list,backoffice-invoice.show-all', only: ['show']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice.create', only: ['store']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice.edit', only: ['update']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice.delete', only: ['destroy']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice.export-csv', only: ['downloadCSV']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice.export-csv-latest', only: ['downloadLatestCSV']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice.export-invoice-plan', only: ['downloadInvoicePlan']),
            new Middleware(CheckPermissionHandler::class.':backoffice-document-assignment.list,document-assignment.list', only: ['documentAssignmentList']),
            new Middleware(CheckPermissionHandler::class.':backoffice-document-assignment.save', only: ['documentAssignmentSave']),
            new Middleware(CheckPermissionHandler::class.':backoffice-invoice-status.edit', only: ['updateInvoiceStatus']),

        ];
    }

    /**
     * Run on instantiate
     */
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * @OA\Get(
     *     path="/invoices",
     *     summary="Get invoices data",
     *     description="The API to get invoices listing",
     *     operationId="InvoiceList",
     *     tags={"Invoices"},
     *
     *     @OA\Parameter(
     *          name="perPage",
     *          description="Get records in one page default is 25",
     *          in="query",
     *
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *
     *     @OA\Parameter(
     *          name="sortBy",
     *          description="Sort by column",
     *          in="query",
     *
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *
     *      @OA\Parameter(
     *         name="sortOrder",
     *          description="Sort order (asc, desc)",
     *          in="query",
     *
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *
     *     @OA\Parameter(
     *         name="search",
     *          description="Search in different column records",
     *          in="query",
     *
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if (PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-invoice.show-all', $request)) {
            return $this->invoiceService->listInvoices($request, true);
        }
        if (Helper::checkPermission('backoffice-invoice.list', $request)) {
            return $this->invoiceService->listInvoices($request, false);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have enough permissions to access this functionality. Missing Permission: backoffice-invoice.show-all or backoffice-invoice.list',
        ], 403);
    }

    public function customerInvoices(Request $request): JsonResponse
    {
        return $this->invoiceService->listInvoices($request, false);
    }

    /**
     * @OA\Post(
     *     path="/invoices",
     *     operationId="CreateInvoice",
     *     tags={"Invoices"},
     *     summary="Create a new invoice",
     *     description="Create a new invoice with detailed product and invoice information",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="companyId", type="string", description="ID of the company"),
     *             @OA\Property(property="invoiceType", type="string", enum={"invoice","invoice-correction", "invoice-storno"}, description="Type of the invoice"),
     *             @OA\Property(property="status", type="string", enum={"draft", "approved", "sent", "warning level 1", "warning level 2", "warning level 3", "paid"}, description="Status of the invoice"),
     *             @OA\Property(property="dueDate", type="string", format="date", description="Due date of the invoice"),
     *             @OA\Property(property="startDate", type="string", format="date", description="Start date of the invoice"),
     *             @OA\Property(property="endDate", type="string", format="date", description="End date of the invoice"),
     *             @OA\Property(property="invoiceDate", type="string", format="date", nullable=true, description="Date of the invoice"),
     *             @OA\Property(property="externalOrderNumber", type="string", nullable=true, description="External order number"),
     *             @OA\Property(property="customNotesFields", type="string", nullable=true, description="Custom notes fields as JSON"),
     *             @OA\Property(property="applyReverseCharge", type="boolean", nullable=true, description="Indicates if reverse charge applies"),
     *             @OA\Property(property="netto", type="number", format="float", nullable=true, description="Net amount"),
     *             @OA\Property(property="taxAmount", type="number", format="float", nullable=true, description="Tax amount"),
     *             @OA\Property(property="totalAmount", type="number", format="float", nullable=true, description="Total amount"),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 description="List of products",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="pos", type="integer", description="Position of the product in the list"),
     *                     @OA\Property(property="articleNumber", type="string", nullable=true, description="Article number of the product"),
     *                     @OA\Property(property="productName", type="string", description="Name of the product"),
     *                     @OA\Property(property="quantity", type="number", format="float", description="Quantity of the product"),
     *                     @OA\Property(property="tax", type="number", format="float", description="Tax applied to the product"),
     *                     @OA\Property(property="productPrice", type="number", format="float", description="Price of the product"),
     *                     @OA\Property(property="nettoTotal", type="number", format="float", description="Net total of the product")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="invoiceId", type="string", description="ID of the created invoice")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     )
     * )
     */
    public function store(InvoiceRequest $request): JsonResponse
    {
        try {
            $validated_data = $request->validated();
            $data = $this->convertKeysToSnakeCase(collect($validated_data));
            $data['user_id'] = $request->get('auth_user_id');

            $invoice = $this->invoiceService->storeInvoice($data);

            return response()->json([
                'success' => true,
                'message' => 'Invoice has been created',
                'data' => $invoice,
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
                'message' => 'Failed to create invoice. Please try again.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *      path="/invoices/{id}",
     *      operationId="getInvoiceById",
     *      tags={"Invoices"},
     *      summary="Get single invoice record",
     *      description="Returns single invoice",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="Id of invoice record",
     *          in="path",
     *          required=true,
     *
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new InvoiceResource($invoice),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/invoices/{id}",
     *     operationId="UpdateInvoice",
     *     tags={"Invoices"},
     *     summary="Update an existing invoice",
     *     description="Update the details of an existing invoice",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the invoice to be updated",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="companyId", type="string", description="ID of the company"),
     *             @OA\Property(property="referenceInvoiceId", type="string", nullable=true, description="ID of the reference invoice"),
     *             @OA\Property(property="invoiceType", type="string", enum={"invoice", "invoice-correction", "invoice-storno"}, description="Type of the invoice"),
     *             @OA\Property(property="status", type="string", enum={"draft", "approved", "sent", "warning level 1", "warning level 2", "warning level 3", "paid"}, description="Status of the invoice"),
     *             @OA\Property(property="dueDate", type="string", format="date", description="Due date of the invoice"),
     *             @OA\Property(property="startDate", type="string", format="date", description="Start date of the invoice"),
     *             @OA\Property(property="endDate", type="string", format="date", description="End date of the invoice"),
     *             @OA\Property(property="invoiceDate", type="string", format="date", nullable=true, description="Date of the invoice"),
     *             @OA\Property(property="externalOrderNumber", type="string", nullable=true, description="External order number"),
     *             @OA\Property(property="customNotesFields", type="object", nullable=true, description="Custom notes fields as JSON"),
     *             @OA\Property(property="applyReverseCharge", type="boolean", nullable=true, description="Indicates if reverse charge applies"),
     *             @OA\Property(property="netto", type="number", format="float", nullable=true, description="Net amount"),
     *             @OA\Property(property="taxAmount", type="number", format="float", nullable=true, description="Tax amount"),
     *             @OA\Property(property="totalAmount", type="number", format="float", nullable=true, description="Total amount"),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 description="List of products",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="pos", type="integer", description="Position of the product in the list"),
     *                     @OA\Property(property="articleNumber", type="string", nullable=true, description="Article number of the product"),
     *                     @OA\Property(property="productName", type="string", description="Name of the product"),
     *                     @OA\Property(property="quantity", type="number", format="float", description="Quantity of the product"),
     *                     @OA\Property(property="tax", type="number", format="float", description="Tax applied to the product"),
     *                     @OA\Property(property="productPrice", type="number", format="float", description="Price of the product"),
     *                     @OA\Property(property="nettoTotal", type="number", format="float", description="Net total of the product")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(InvoiceRequest $request, string $id): JsonResponse
    {
        try {
            $validated_data = $request->validated();
            $data = $this->convertKeysToSnakeCase(collect($validated_data));
            $this->invoiceService->updateInvoice($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Invoice has been updated',
            ]);
        } catch (InvoiceNotFoundException $e) {
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
                'message' => 'Failed to update invoice. Please try again.',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/update-invoice-status/{id}",
     *     operationId="UpdateInvoiceStatus",
     *     tags={"Invoices"},
     *     summary="Update the status of an invoice",
     *     description="Update the status of an invoice to approved, sent, paid, or warning levels",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the invoice to be updated",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"approved", "sent", "paid", "warning level 1", "warning level 2", "warning level 3"},
     *                 description="The new status to set for the invoice"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice status updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function updateInvoiceStatus(Request $request, string $id): JsonResponse
    {
        return $this->invoiceService->updateInvoiceStatus($request, $id);
    }

    /**
     * @OA\Get(
     *     path="/global-settings/document-assignment",
     *     operationId="DocumentAssignmentList",
     *     tags={"Global Settings"},
     *     summary="Get list of document assignments",
     *     description="Retrieve a list of global settings for invoice templates",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 additionalProperties={
     *                     "type": "string"
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function documentAssignmentList(): JsonResponse
    {
        return $this->invoiceService->documentAssignmentList();
    }

    /**
     * @OA\Post(
     *     path="/global-settings/document-assignment",
     *     operationId="DocumentAssignmentSave",
     *     tags={"Global Settings"},
     *     summary="Save document assignment settings",
     *     description="Save or update global settings for invoice templates",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *             required={"invoiceTemplateId", "invoiceCorrectionTemplateId", "invoiceStornoTemplateId"},
     *
     *             @OA\Property(
     *                 property="invoiceTemplateId",
     *                 type="string",
     *                 description="ID for the invoice template"
     *             ),
     *             @OA\Property(
     *                 property="invoiceCorrectionTemplateId",
     *                 type="string",
     *                 description="ID for the invoice correction template"
     *             ),
     *             @OA\Property(
     *                 property="invoiceStornoTemplateId",
     *                 type="string",
     *                 description="ID for the invoice storno template"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Record saved successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or failure",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function documentAssignmentSave(Request $request): JsonResponse
    {
        return $this->invoiceService->documentAssignmentSave($request);
    }

    /**
     * @OA\Delete(
     *     path="/invoices/{id}",
     *     operationId="DeleteInvoice",
     *     tags={"Invoices"},
     *     summary="Delete an invoice",
     *     description="Delete an invoice by its ID",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the invoice to be deleted",
     *
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->invoiceService->deleteInvoice($id);
    }

    /**
     * @OA\Get(
     *     path="/invoices/download/csv",
     *     operationId="DownloadCSV",
     *     tags={"Invoices"},
     *     summary="Download invoices as CSV",
     *     description="Fetches and downloads a CSV file of invoices. If not an admin portal, filters invoices by the authenticated company.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="CSV download initiated",
     *
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="attachment; filename=invoices.csv",
     *
     *             @OA\Schema(type="string")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="text/csv"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function downloadCSV(Request $request): StreamedResponse|JsonResponse
    {
        if (PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-invoice.show-all', $request)) {
            return $this->downloadInvoice($request, true);
        }

        return $this->downloadInvoice($request, false);
    }

    public function downloadCustomerCSV(Request $request): StreamedResponse|JsonResponse
    {
        return $this->downloadInvoice($request, false);
    }

    private function downloadInvoice(Request $request, bool $isAdmin): StreamedResponse|JsonResponse
    {
        $companyId = Helper::getCompanyId($request->bearerToken());

        $invoices = new Invoice;

        if (! $isAdmin && $companyId !== null) {
            $invoices = $invoices->where('company_id', $companyId);
        }

        $fileName = 'invoices.csv';
        $invoices = $invoices->get();

        return $this->invoiceService->createCSV($invoices, $fileName, 'invoice', false, $request);
    }

    /**
     * @OA\Get(
     *     path="/invoices/download/latest-csv",
     *     operationId="DownloadLatestCSV",
     *     tags={"Invoices"},
     *     summary="Download latest invoices as CSV",
     *     description="Fetches and downloads a CSV file of invoices. If not an admin portal, filters invoices by the authenticated company.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Latest CSV download initiated",
     *
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="attachment; filename=invoices.csv",
     *
     *             @OA\Schema(type="string")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="text/csv"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function downloadLatestCSV(Request $request): StreamedResponse|JsonResponse
    {
        if (PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-invoice.show-all', $request)) {
            return $this->downloadLatestInvoice($request, true);
        }

        return $this->downloadLatestInvoice($request, false);
    }

    public function downloadLatestCustomerCSV(Request $request): StreamedResponse|JsonResponse
    {
        return $this->downloadLatestInvoice($request, false);
    }

    private function downloadLatestInvoice(Request $request, bool $isAdmin): StreamedResponse|JsonResponse
    {
        return $this->downloadCSV($request);
    }

    /**
     * @OA\Get(
     *     path="/invoices/export-invoice-plan",
     *     operationId="DownloadInvoicePlan",
     *     tags={"Invoices"},
     *     summary="Download invoice plan as CSV",
     *     description="Fetches and downloads a CSV file of invoices. If not an admin portal, filters invoices by the authenticated company.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Latest CSV download initiated",
     *
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="attachment; filename=invoices.csv",
     *
     *             @OA\Schema(type="string")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="text/csv"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function downloadInvoicePlan(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $companyId = Helper::getCompanyId($request->bearerToken());
        $isAdmin = false;

        if (PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-invoice.show-all', $request)) {
            $isAdmin = true;
        }

        if ($companyId === null && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        return Excel::download(new InvoicePlanExport($companyId, $isAdmin), 'invoiceplan.csv');
    }
}
