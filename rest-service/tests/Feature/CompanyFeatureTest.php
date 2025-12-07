<?php

namespace Tests\Feature;

use App\Models\Company;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CompanyFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function generateTestToken(array $payload = []): string
    {
        $defaultPayload = [
            'company_id' => 'test-company-id',
            'user_id' => 'test-user-id',
            'roles' => ['admin'],
            'scopes' => [
                'dash_invoice' => []
            ],
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $payload = array_merge($defaultPayload, $payload);

        $jwtKey = Config::get('session.JWT_KEY') ?: 'test-secret-key-for-testing-only';
        return JWT::encode($payload, $jwtKey, 'HS256');
    }

    private function withAuth(array $tokenPayload = []): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->generateTestToken($tokenPayload),
            'Accept' => 'application/json',
        ];
    }

    public function test_can_list_companies_with_admin_token(): void
    {
        Company::factory()->count(3)->create();

        $response = $this->getJson('/api/companies', $this->withAuth(['roles' => ['admin']]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'companyNumber',
                        'companyName',
                        'displayName',
                    ]
                ],
                'current_page',
                'total',
            ]);
    }

    public function test_can_list_companies_with_permission(): void
    {
        $company = Company::factory()->create();
        
        $token = $this->generateTestToken([
            'company_id' => $company->id,
            'roles' => ['user'],
            'scopes' => [
                'dash_invoice' => [
                    [1] // Assuming permission index 1 maps to 'backoffice-company.list'
                ]
            ]
        ]);

        $response = $this->getJson('/api/companies', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
    }

    public function test_cannot_list_companies_without_permission(): void
    {
        $token = $this->generateTestToken([
            'roles' => ['user'],
            'scopes' => [
                'dash_invoice' => []
            ]
        ]);

        $response = $this->getJson('/api/companies', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have enough permissions to access this functionality. Missing Permission:backoffice-company.show-all or backoffice-company.list'
            ]);
    }

    public function test_can_create_company_with_valid_data(): void
    {
        $data = [
            'companyName' => 'Test Company',
            'addressLine1' => '123 Test Street',
            'city' => 'Test City',
            'country' => 'US',
            'zipCode' => '12345',
        ];

        $response = $this->postJson('/api/companies', $data, $this->withAuth());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer has been created',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'company_name',
                    'company_number',
                ]
            ]);

        $this->assertDatabaseHas('companies', [
            'company_name' => 'Test Company',
        ]);
    }

    public function test_cannot_create_company_without_required_fields(): void
    {
        $data = [
            'companyName' => '', // Missing required field
        ];

        $response = $this->postJson('/api/companies', $data, $this->withAuth());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['companyName', 'addressLine1', 'city', 'country', 'zipCode']);
    }

    public function test_cannot_create_company_with_duplicate_name(): void
    {
        Company::factory()->create(['company_name' => 'Existing Company']);

        $data = [
            'companyName' => 'Existing Company',
            'addressLine1' => '123 Test Street',
            'city' => 'Test City',
            'country' => 'US',
            'zipCode' => '12345',
        ];

        $response = $this->postJson('/api/companies', $data, $this->withAuth());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['companyName']);
    }

    public function test_can_show_company_by_id(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
            'company_number' => 'C1001',
        ]);

        $response = $this->getJson("/api/companies/{$company->id}", $this->withAuth());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'modelData' => [
                    'id',
                    'companyName',
                    'displayName',
                    'vatId',
                ]
            ]);
    }

    public function test_show_returns_empty_when_company_not_found(): void
    {
        $response = $this->getJson('/api/companies/non-existent-id', $this->withAuth());

        $response->assertStatus(200)
            ->assertJson([
                'modelData' => []
            ]);
    }

    public function test_can_update_company(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Original Name',
            'city' => 'Original City',
        ]);

        $data = [
            'companyName' => 'Updated Name',
            'city' => 'Updated City',
        ];

        $response = $this->putJson("/api/companies/{$company->id}", $data, $this->withAuth());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer has been updated',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'company_name' => 'Updated Name',
            'city' => 'Updated City',
        ]);
    }

    public function test_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $response = $this->deleteJson("/api/companies/{$company->id}", [], $this->withAuth());

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Record deleted.'
            ]);

        $this->assertSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_can_restore_deleted_company(): void
    {
        $company = Company::factory()->create();
        $company->delete();

        $response = $this->postJson("/api/companies/{$company->id}/restore", [], $this->withAuth());

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Record restored.'
            ]);

        $this->assertNotSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_can_check_vat_id(): void
    {
        $data = [
            'vatId' => 'DE123456789',
        ];

        $response = $this->postJson('/api/check-vat-id', $data, [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'valid',
            ]);
    }

    public function test_can_get_company_credits(): void
    {
        $company = Company::factory()->create([
            'credits' => 100.50,
        ]);

        $token = $this->generateTestToken([
            'company_id' => $company->id,
        ]);

        $response = $this->getJson('/api/get-credits', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'credits' => 100.50,
            ]);
    }

    public function test_can_download_companies_csv_report(): void
    {
        Company::factory()->count(3)->create();

        $response = $this->get('/api/companies/download/csv', $this->withAuth());

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="companies_report.csv"');
    }

    public function test_requires_authentication_for_protected_routes(): void
    {
        $response = $this->getJson('/api/companies');

        $response->assertStatus(419)
            ->assertJson([
                'message' => 'Token is invalid or expired.'
            ]);
    }

    public function test_company_list_includes_display_name(): void
    {
        $company = Company::factory()->create([
            'company_name' => 'Test Company',
            'company_number' => 'C1001',
        ]);

        $response = $this->getJson('/api/companies', $this->withAuth(['roles' => ['admin']]));

        $response->assertStatus(200)
            ->assertJsonPath('data.0.displayName', 'C1001 Test Company');
    }
}
