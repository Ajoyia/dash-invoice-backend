<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sender_id' => Company::factory(),
            'reference_invoice_id' => null,
            'invoice_type' => 'invoice',
            'status' => 'draft',
            'due_date' => $this->faker->date(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'invoice_date' => $this->faker->date(),
            'paid_at' => null,
            'external_order_number' => $this->faker->optional()->numerify('ORD-####'),
            'user_id' => null,
            'apply_reverse_charge' => false,
            'netto' => $this->faker->randomFloat(2, 100, 10000),
            'tax_amount' => $this->faker->randomFloat(2, 10, 1000),
            'total_amount' => $this->faker->randomFloat(2, 110, 11000),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => $this->faker->date(),
        ]);
    }

    public function invoiceCorrection(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_type' => 'invoice-correction',
        ]);
    }

    public function invoiceStorno(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_type' => 'invoice-storno',
        ]);
    }
}
