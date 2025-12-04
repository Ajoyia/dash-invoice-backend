<?php

namespace App\Http\Controllers;

use App\Helpers\CustomerHelper;
use App\Helpers\Helper;
use App\Services\VatlayerService;
use App\Http\Requests\CompanyRequest;
use App\Imports\CustomersImport;
use App\Utils\PermissionChecker;
use App\Http\Middleware\CheckPermissionHandler;
use Illuminate\Support\Facades\App;
use App\Models\Company;
use Illuminate\Routing\Controllers\HasMiddleware;
use Exception;
use Illuminate\Http\Request;
use App\Traits\CustomHelper;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;

class CompanyController extends Controller implements HasMiddleware
{
    use CustomHelper;
    protected $customerHelper;

    public function __construct(CustomerHelper $customerHelper)
    {
        $this->customerHelper = $customerHelper;
    }

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

    public function checkVatId(Request $request, VatlayerService $vatlayer)
    {
        $vatNumber = $request->input('vatId');

        $result = $vatlayer->validate($vatNumber);

        if ($result['valid'] ?? false) {
            return response()->json([
                'data' => $result,
                'valid' => true,
            ]);
        }

        return response()->json([
            'data' => $result,
            'valid' => false,
        ]);
    }

    public function index(Request $request)
    {
        if (PermissionChecker::isAdmin($request) || Helper::checkPermission('backoffice-company.show-all', $request)) {
            return $this->customerHelper->index($request, true);
        }
        if (Helper::checkPermission('backoffice-company.list', $request)) {
            return $this->customerHelper->index($request, false);
        }
        return response()->json(['message' => 'You do not have enough permissions to access this functionality. Missing Permission:backoffice-company.show-all or backoffice-company.list'], 403);
    }

    public function store(CompanyRequest $request)
    {
        return $this->customerHelper->store($request);
    }

    public function show(Request $request, $id)
    {
        return $this->customerHelper->show($request, $id);
    }

    public function update(CompanyRequest $request, $id)
    {
        return $this->customerHelper->update($request, $id);
    }

    public function createReport()
    {
        $file_name = 'companies_report.csv';
        $companies = Company::query()->get();
        return $this->customerHelper->createCSV($companies, $file_name);
    }

    public function sendRegisterMail(Request $request)
    {
        try {
            $userData = [
                "mail"     => $request->email,
                "password" => "fwed2uh345ert",
                "mail_template_id" => null,
                "from_mail" => null,
                "first_name" => ($request->firstName ?? $request->first_name) ?? null,
                "last_name" => ($request->lastName ?? $request->last_name) ?? null,
                "cc"               => null,
                "bcc"              => null,
                "only_new"         => true
            ];

            $redis = new \Redis();
            $host = config('authredis.connection.host') ?: env('REDIS_HOST', '127.0.0.1');
            $port = config('authredis.connection.port') ?: env('REDIS_PORT', 6379);
            $password = config('authredis.connection.password') ?: env('REDIS_PASSWORD', null);
            $redis->connect($host, $port);
            if ($password) {
                $redis->auth($password);
            }
            $redis->lPush('users_queue', json_encode($userData));

            return response()->json(['message' => "Mail sent successfully"]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        return $this->customerHelper->destroy($id);
    }

    public function restore($id)
    {
        $model = Company::find($id);
        $model->restore();
        return response()->json(['message' => 'Record restored.'], 200);
    }

    public function getCredits(Request $request)
    {
        $company_id = Helper::getCompanyId($request->bearerToken());
        $company = Company::find($company_id);

        if ($company) {
            return response()->json([
                'credits' => $company->credits ?? 0,
            ]);
        } else {
            return response()->json([
                'credits' => 0,
            ]);
        }
    }

    public function getResellerPartner(Request $request)
    {
        return $this->customerHelper->getResellerPartner($request);
    }

    public function getResellersPartners(Request $request)
    {
        return $this->customerHelper->getResellersPartners($request);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);

        $locale = $request->header('Accept-Language', 'en');
        App::setLocale($locale);

        try {
            Excel::import(new CustomersImport, $request->file('file'));
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.csv_upload_error'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "CSV imported successfully"
        ]);
    }

    public function uploadCompanyLogo(Request $request)
    {
        $request->validate([
            'image' => 'required',
        ]);

        try {
            $company_id = Helper::getCompanyId($request->bearerToken());
            $loggedInCompany = Company::find($company_id);
            if ($loggedInCompany) {
                $this->storeAttachment($request->image, $loggedInCompany);
            } else {
                return response()->json([
                    'message' => 'Company not found',
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Logo uploaded successfully"
        ]);
    }

    public function deleteCompanyLogo(Request $request)
    {
        try {
            $company_id = Helper::getCompanyId($request->bearerToken());
            $loggedInCompany = Company::find($company_id);
            if ($loggedInCompany) {
                Helper::removeAttachment($loggedInCompany);
            } else {
                return response()->json([
                    'message' => 'Company not found',
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Logo deleted successfully"
        ]);
    }

    private function storeAttachment($request, $model): void
    {
        Helper::removeAttachment($model);
        Helper::saveAttachment($request, $model);
    }
}
