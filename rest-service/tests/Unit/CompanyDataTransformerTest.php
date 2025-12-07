<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\Company\CompanyDataTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDataTransformerTest extends TestCase
{
    use RefreshDatabase;

    private CompanyDataTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new CompanyDataTransformer();
    }

    public function test_can_transform_company_for_list(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
            'company_number' => 'C1001',
            'vat_id' => 'VAT123',
            'city' => 'Test City',
            'country' => 'US',
            'credits' => 100.50,
            'status' => 'active',
        ]);

        $result = $this->transformer->transformForList($company);

        $this->assertIsArray($result);
        $this->assertEquals($company->id, $result['id']);
        $this->assertEquals('Test Company', $result['companyName']);
        $this->assertEquals('C1001', $result['companyNumber']);
        $this->assertEquals('C1001 Test Company', $result['displayName']);
        $this->assertEquals('VAT123', $result['vatId']);
        $this->assertEquals('Test City', $result['city']);
        $this->assertEquals(100.50, $result['credits']);
        $this->assertEquals('active', $result['status']);
    }

    public function test_can_transform_company_for_detail(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
            'company_number' => 'C1001',
            'vat_id' => 'VAT123',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Suite 100',
            'city' => 'Test City',
            'zip_code' => '12345',
            'country' => 'US',
            'credits' => 100.50,
            'status' => 'active',
        ]);

        $result = $this->transformer->transformForDetail($company);

        $this->assertIsArray($result);
        $this->assertEquals($company->id, $result['id']);
        $this->assertEquals('Test Company', $result['companyName']);
        $this->assertEquals('C1001 Test Company', $result['displayName']);
        $this->assertEquals('VAT123', $result['vatId']);
        $this->assertEquals('123 Main St', $result['addressLine1']);
        $this->assertEquals('Suite 100', $result['addressLine2']);
        $this->assertEquals('Test City', $result['city']);
        $this->assertEquals(100.50, $result['credits']);
    }

    public function test_transform_includes_invoice_sum_when_calculated(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
        ]);

        // Mock invoices relationship
        $company->setRelation('invoices', collect([
            (object)['total_amount' => 100.00],
            (object)['total_amount' => 200.00],
        ]));

        $result = $this->transformer->transformForList($company);

        $this->assertEquals(300.00, $result['invoiceSum']);
    }
}
