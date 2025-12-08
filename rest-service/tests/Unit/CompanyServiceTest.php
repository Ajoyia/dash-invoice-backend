<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Repositories\CompanyRepositoryInterface;
use App\Services\Company\CompanyDataTransformer;
use App\Services\Company\CompanyService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    private CompanyService $service;

    private $mockRepository;

    private $mockTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(CompanyRepositoryInterface::class);
        $this->mockTransformer = new CompanyDataTransformer;

        $this->service = new CompanyService(
            $this->mockRepository,
            $this->mockTransformer
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_get_company_by_id(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
        ]);

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($company->id)
            ->andReturn($company);

        $request = Request::create('/companies/'.$company->id);
        $result = $this->service->getCompanyById($company->id, $request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('modelData', $result);
        $this->assertEquals('Test Company', $result['modelData']['companyName']);
    }

    public function test_get_company_by_id_returns_empty_when_not_found(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $request = Request::create('/companies/non-existent-id');
        $result = $this->service->getCompanyById('non-existent-id', $request);

        $this->assertEquals(['modelData' => []], $result);
    }

    public function test_can_get_company_credits(): void
    {
        $company = Company::factory()->create([
            'credits' => 100.50,
        ]);

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($company->id)
            ->andReturn($company);

        $credits = $this->service->getCompanyCredits($company->id);

        $this->assertEquals(100.50, $credits);
    }

    public function test_get_company_credits_returns_zero_when_company_not_found(): void
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $credits = $this->service->getCompanyCredits('non-existent-id');

        $this->assertEquals(0.0, $credits);
    }

    public function test_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $this->mockRepository
            ->shouldReceive('findOrFail')
            ->once()
            ->with($company->id)
            ->andReturn($company);

        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with($company)
            ->andReturn(true);

        $result = $this->service->deleteCompany($company->id);

        $this->assertTrue($result);
    }

    public function test_can_restore_company(): void
    {
        $company = Company::factory()->create();
        $company->delete();

        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($company->id)
            ->andReturn($company);

        $this->mockRepository
            ->shouldReceive('restore')
            ->once()
            ->with($company)
            ->andReturn(true);

        $result = $this->service->restoreCompany($company->id);

        $this->assertTrue($result);
    }
}
