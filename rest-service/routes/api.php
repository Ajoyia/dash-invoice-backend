<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceReminderLevelController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe-webhook', [StripeController::class, 'stripeWebhookNew']);

Route::post('/check-vat-id', [CompanyController::class, 'checkVatId']);
Route::get('get-partner-companies', [CompanyController::class, 'getResellersPartners']);
Route::post('register-company', [CompanyController::class, 'storeReferral']);

Route::get('/captcha/api', function () {
    return response()->json([
        'key' => app('captcha')->create('default', true),
        'img' => captcha_src('default'),
    ]);
});

Route::middleware(['user.permissions'])->group(function () {
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

    Route::get('get-credits', [CompanyController::class, 'getCredits']);
    Route::get('get-reseller-partner', [CompanyController::class, 'getResellerPartner']);
    Route::get('balance-history/{id}', [CompanyController::class, 'getBalanceHistory']);
    Route::get('commission-history/{id}', [CompanyController::class, 'getCommissionHistory']);
    Route::get('referral', [CompanyController::class, 'referral']);
    Route::post('companies-import', [CompanyController::class, 'importCsv']);
    Route::post('upload-company-logo', [CompanyController::class, 'uploadCompanyLogo']);
    Route::post('delete-company-logo', [CompanyController::class, 'deleteCompanyLogo']);
    Route::post('company/update-threshold', [CompanyController::class, 'updateThreshold']);
    Route::apiResource('companies', CompanyController::class);
    Route::get('/credit-history/{id}', [CompanyController::class, 'getCreditHistory']);
    Route::post('/send-register-mail', [CompanyController::class, 'sendRegisterMail']);
    Route::get('/companies/download/csv', [CompanyController::class, 'createReport']);
    Route::post('save-company-owners', [CompanyController::class, 'saveCompanyOwners']);

    Route::apiResource('invoice-reminder-level', InvoiceReminderLevelController::class);

    Route::get('/stripe/products', [StripeController::class, 'products']);
    Route::post('/stripe/subscription-checkout', [StripeController::class, 'stripeSubscriptionCheckout']);
    Route::post('/stripe/direct-checkout', [StripeController::class, 'stripeDirectCheckout']);
    Route::post('/stripe/checkout/payment-method', [StripeController::class, 'stripeCheckoutPaymentMethod']);
    Route::post('/stripe/session', [StripeController::class, 'getSessionDetailNew']);
    Route::get('/stripe/company-subscription', [StripeController::class, 'getCompanySubscription']);
    Route::get('/stripe/cancel-subscription', [StripeController::class, 'cancelSubscription']);
    Route::get('/stripe/payment-method', [StripeController::class, 'companyPaymentMethod']);
    Route::get('/stripe/customer/default-payment-method/{id}', [StripeController::class, 'setDefaultPaymentMethod']);
    Route::post('/stripe/upgrade-plan', [StripeController::class, 'upgradePlan']);
    Route::get('/stripe/revert-upgrade-plan', [StripeController::class, 'revertUpgradePlan']);
    Route::post('/stripe/pause-subscription', [StripeController::class, 'pauseSubscription']);
    Route::get('/stripe/resume-subscription', [StripeController::class, 'resumeSubscription']);
});

Route::post('/stripe/webhook', [StripeController::class, 'stripeWebhookNew']);
