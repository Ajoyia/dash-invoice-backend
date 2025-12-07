<?php

namespace Tests\Unit;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_name_accessor_combines_company_number_and_name(): void
    {
        $company = Company::factory()->create([
            'company_number' => 'C1001',
            'company_name' => 'Test Company',
        ]);

        $this->assertEquals('C1001 Test Company', $company->display_name);
    }

    public function test_display_name_handles_missing_company_number(): void
    {
        $company = Company::factory()->create([
            'company_number' => null,
            'company_name' => 'Test Company',
        ]);

        $this->assertEquals(' Test Company', $company->display_name);
    }

    public function test_display_name_handles_missing_company_name(): void
    {
        $company = Company::factory()->create([
            'company_number' => 'C1001',
            'company_name' => 'Test',
        ]);

        $this->assertEquals('C1001 Test', $company->display_name);
    }

    public function test_display_name_is_appended_to_array(): void
    {
        $company = Company::factory()->create([
            'company_number' => 'C1001',
            'company_name' => 'Test Company',
        ]);

        $array = $company->toArray();

        $this->assertArrayHasKey('display_name', $array);
        $this->assertEquals('C1001 Test Company', $array['display_name']);
    }

    public function test_company_uses_soft_deletes(): void
    {
        $company = Company::factory()->create();

        $company->delete();

        $this->assertSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }
}
