<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::post('/check-vat-id', [CompanyController::class, 'checkVatId']);

Route::get('/captcha/api', function () {
    return response()->json([
        'key' => app('captcha')->create('default', true),
        'img' => captcha_src('default'),
    ]);
});

Route::middleware(['user.permissions'])->group(function () {

    //Dashboard API's
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('backoffice-dashboard', [DashboardController::class, 'backOfficeDashboard']);

    //Invoice API's
    Route::get('/invoices/download/csv', [InvoiceController::class, 'downloadCSV']);
    Route::get('/invoices/download/customer-csv', [InvoiceController::class, 'downloadCustomerCSV']);
    Route::get('/invoices/download/latest-csv', [InvoiceController::class, 'downloadLatestCSV']);
    Route::get('/invoices/download/latestcustomer-csv', [InvoiceController::class, 'downloadLatestCustomerCSV']);
    Route::get('/invoices/export-invoice-plan', [InvoiceController::class, 'downloadInvoicePlan']);
    Route::apiResource('/invoices', InvoiceController::class);
    Route::get('/customer-invoices', [InvoiceController::class, 'customerInvoices']);
    Route::patch('/update-invoice-status/{id}', [InvoiceController::class, 'updateInvoiceStatus']);
    Route::get('global-settings/document-assignment', [InvoiceController::class, 'documentAssignmentList']);
    Route::post('global-settings/document-assignment', [InvoiceController::class, 'documentAssignmentSave']);

    //Company API's
    Route::get('get-credits', [CompanyController::class, 'getCredits']);
    Route::get('balance-history/{id}', [CompanyController::class, 'getBalanceHistory']);
    Route::get('commission-history/{id}', [CompanyController::class, 'getCommissionHistory']);
    Route::post('companies-import', [CompanyController::class, 'importCsv']);
    Route::post('upload-company-logo', [CompanyController::class, 'uploadCompanyLogo']);
    Route::post('delete-company-logo', [CompanyController::class, 'deleteCompanyLogo']);
    Route::post('company/update-threshold', [CompanyController::class, 'updateThreshold']);
    Route::apiResource('companies', CompanyController::class);
    Route::get('/credit-history/{id}', [CompanyController::class, 'getCreditHistory']);
    Route::post('/send-register-mail', [CompanyController::class, 'sendRegisterMail']);
    Route::get('/companies/download/csv', [CompanyController::class, 'createReport']);
    Route::post('save-company-owners', [CompanyController::class, 'saveCompanyOwners']);

});
