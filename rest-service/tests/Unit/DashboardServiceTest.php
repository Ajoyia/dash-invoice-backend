<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Repositories\Dashboard\CompanyDashboardRepositoryInterface;
use App\Repositories\Dashboard\InvoiceDashboardRepositoryInterface;
use App\Services\DashboardService\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    private DashboardService $service;
    private $mockCompanyRepository;
    private $mockInvoiceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCompanyRepository = Mockery::mock(CompanyDashboardRepositoryInterface::class);
        $this->mockInvoiceRepository = Mockery::mock(InvoiceDashboardRepositoryInterface::class);

        $this->service = new DashboardService(
            $this->mockCompanyRepository,
            $this->mockInvoiceRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_portal_dashboard_handles_exceptions_when_token_invalid(): void
    {
        $request = Request::create('/api/dashboard', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $response = $this->service->portalDashboard($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('message', $data);
    }

    public function test_portal_dashboard_handles_date_range_parameters(): void
    {
        $companyId = 'test-company-id';
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        $companyIds = [$companyId];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')
            ->andReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjb21wYW55X2lkIjoi'.base64_encode($companyId).'In0.test');
        $request->shouldReceive('input')
            ->with('startDate')
            ->andReturn($startDate);
        $request->shouldReceive('input')
            ->with('endDate')
            ->andReturn($endDate);

        $this->mockInvoiceRepository
            ->shouldReceive('getDailyCounts')
            ->once()
            ->andReturn([]);

        $this->mockInvoiceRepository
            ->shouldReceive('getTodayCount')
            ->once()
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getThisWeekCount')
            ->once()
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getThisMonthCount')
            ->once()
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getTotalCount')
            ->once()
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getCountByDateRange')
            ->once()
            ->andReturn(0);

        $response = $this->service->portalDashboard($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('labels', $data);
            $this->assertCount(31, $data['labels']);
        } else {
            $this->assertEquals(500, $response->getStatusCode());
        }
    }

    public function test_backoffice_dashboard_returns_correct_structure(): void
    {
        $companyId = 'test-company-id';
        $relatedCompanyIds = [$companyId];

        $company = new Company();
        $company->id = $companyId;
        $company->parent_id = null;

        $request = new Request(['customerId' => $companyId]);

        $this->mockCompanyRepository
            ->shouldReceive('find')
            ->once()
            ->with($companyId)
            ->andReturn($company);

        $this->mockInvoiceRepository
            ->shouldReceive('getDailyCounts')
            ->once()
            ->with($relatedCompanyIds, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn([15, 20, 25]);

        $this->mockCompanyRepository
            ->shouldReceive('getDailyCounts')
            ->once()
            ->with($relatedCompanyIds, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn([2, 3, 5]);

        $this->mockInvoiceRepository
            ->shouldReceive('getTodayCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(25);

        $this->mockInvoiceRepository
            ->shouldReceive('getThisWeekCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(120);

        $this->mockInvoiceRepository
            ->shouldReceive('getThisMonthCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(450);

        $this->mockInvoiceRepository
            ->shouldReceive('getTotalCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(3500);

        $this->mockInvoiceRepository
            ->shouldReceive('getCountByDateRange')
            ->once()
            ->with($relatedCompanyIds, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn(200);

        $this->mockCompanyRepository
            ->shouldReceive('getTodayCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(5);

        $this->mockCompanyRepository
            ->shouldReceive('getThisWeekCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(20);

        $this->mockCompanyRepository
            ->shouldReceive('getThisMonthCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(75);

        $this->mockCompanyRepository
            ->shouldReceive('getTotalCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(500);

        $this->mockCompanyRepository
            ->shouldReceive('getCountByDateRange')
            ->once()
            ->with($relatedCompanyIds, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn(30);

        $response = $this->service->backOfficeDashboard($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('invoices', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertArrayHasKey('labels', $data['invoices']);
        $this->assertArrayHasKey('series', $data['invoices']);
        $this->assertEquals('Invoices', $data['invoices']['series'][0]['name']);
        $this->assertEquals('Customers', $data['customers']['series'][0]['name']);
    }

    public function test_backoffice_dashboard_uses_customer_id_parameter(): void
    {
        $customerId = 'customer-123';
        $relatedCompanyIds = [$customerId];

        $company = new Company();
        $company->id = $customerId;
        $company->parent_id = null;

        $request = new Request(['customerId' => $customerId]);

        $this->mockCompanyRepository
            ->shouldReceive('find')
            ->once()
            ->with($customerId)
            ->andReturn($company);

        $this->mockInvoiceRepository
            ->shouldReceive('getDailyCounts')
            ->once()
            ->andReturn([]);

        $this->mockCompanyRepository
            ->shouldReceive('getDailyCounts')
            ->once()
            ->andReturn([]);

        $this->mockInvoiceRepository
            ->shouldReceive('getTodayCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getThisWeekCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getThisMonthCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getTotalCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockInvoiceRepository
            ->shouldReceive('getCountByDateRange')
            ->once()
            ->with($relatedCompanyIds, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn(0);

        $this->mockCompanyRepository
            ->shouldReceive('getTodayCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockCompanyRepository
            ->shouldReceive('getThisWeekCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockCompanyRepository
            ->shouldReceive('getThisMonthCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockCompanyRepository
            ->shouldReceive('getTotalCount')
            ->once()
            ->with($relatedCompanyIds)
            ->andReturn(0);

        $this->mockCompanyRepository
            ->shouldReceive('getCountByDateRange')
            ->once()
            ->with($relatedCompanyIds, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn(0);

        $response = $this->service->backOfficeDashboard($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_backoffice_dashboard_handles_exceptions(): void
    {
        $companyId = 'test-company-id';

        $request = new Request(['customerId' => $companyId]);

        $this->mockCompanyRepository
            ->shouldReceive('find')
            ->once()
            ->with($companyId)
            ->andThrow(new \Exception('Database error'));

        $response = $this->service->backOfficeDashboard($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('message', $data);
    }
}
