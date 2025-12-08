<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'company_number' => $this->faker->optional()->numerify('COMP-####'),
            'vat_id' => $this->faker->optional()->numerify('VAT#########'),
            'phone' => $this->faker->optional()->phoneNumber(),
            'status' => 'active',
            'invoice_email' => $this->faker->optional()->email(),
            'warning_invoice_email' => $this->faker->optional()->email(),
            'apply_reverse_charge' => false,
            'external_order_number' => $this->faker->optional()->numerify('ORD-####'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
