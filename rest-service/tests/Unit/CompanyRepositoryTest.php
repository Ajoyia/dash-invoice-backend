<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CompanyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CompanyRepository();
    }

    public function test_can_find_company_by_id(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
            'company_number' => 'C1001',
        ]);

        $result = $this->repository->find($company->id);

        $this->assertNotNull($result);
        $this->assertEquals($company->id, $result->id);
        $this->assertEquals('Test Company', $result->company_name);
    }

    public function test_find_returns_null_when_company_not_found(): void
    {
        $result = $this->repository->find('non-existent-id');

        $this->assertNull($result);
    }

    public function test_can_create_company(): void
    {
        $data = [
            'company_name' => 'New Company',
            'company_number' => 'C1002',
            'vat_id' => 'VAT123',
            'city' => 'Test City',
            'country' => 'US',
            'zip_code' => '12345',
            'status' => 'new',
        ];

        $company = $this->repository->create($data);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('New Company', $company->company_name);
        $this->assertEquals('C1002', $company->company_number);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'company_name' => 'New Company',
        ]);
    }

    public function test_can_update_company(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Original Name',
        ]);

        $updatedData = [
            'company_name' => 'Updated Name',
            'city' => 'New City',
        ];

        $result = $this->repository->update($company, $updatedData);

        $this->assertEquals('Updated Name', $result->company_name);
        $this->assertEquals('New City', $result->city);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'company_name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $result = $this->repository->delete($company);

        $this->assertTrue($result);
        $this->assertSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_can_restore_deleted_company(): void
    {
        $company = Company::factory()->create();
        $company->delete();

        $result = $this->repository->restore($company);

        $this->assertTrue($result);
        $this->assertNotSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_can_get_all_companies(): void
    {
        Company::factory()->count(3)->create();

        $result = $this->repository->getAll();

        $this->assertCount(3, $result);
    }

    public function test_generate_company_number_creates_unique_number(): void
    {
        // RefreshDatabase ensures clean state, but let's be explicit
        Company::query()->truncate();
        
        // Create a company with a known number
        Company::factory()->create(['company_number' => 'C1000']);

        // Generate first number - should be C1001 (next after C1000)
        $number1 = $this->repository->generateCompanyNumber();
        $this->assertEquals('C1001', $number1, 'First generated number should be C1001 after C1000');
        
        // Create a company with the first generated number to update the max
        Company::factory()->create(['company_number' => $number1]);
        
        // Generate second number - should be C1002 (next after C1001)
        $number2 = $this->repository->generateCompanyNumber();
        $this->assertEquals('C1002', $number2, 'Second generated number should be C1002 after C1001');
        
        // Verify both numbers are unique
        $this->assertNotEquals($number1, $number2, 'Generated numbers should be unique');
    }
}
