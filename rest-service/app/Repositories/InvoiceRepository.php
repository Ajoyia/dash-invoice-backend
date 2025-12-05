<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Services\InvoiceService\Interfaces\InvoiceNumberGeneratorInterface;
use App\Traits\CustomHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceRepository implements InvoiceRepositoryInterface
{
	use CustomHelper;

	protected $invoiceNumberGenerator;

	public function __construct(InvoiceNumberGeneratorInterface $invoiceNumberGenerator)
	{
		$this->invoiceNumberGenerator = $invoiceNumberGenerator;
	}

	public function getInvoices(array $filters, bool $isAdmin): LengthAwarePaginator
	{
		$query = Invoice::query()
			->select([
				'invoices.id',
				'invoices.invoice_number',
				'invoices.company_id',
				'invoices.invoice_type',
				'invoices.status',
				'invoices.due_date',
				'invoices.start_date',
				'invoices.end_date',
				'invoices.invoice_date',
				'invoices.netto',
				'invoices.tax_amount',
				'invoices.total_amount',
				'invoices.created_at',
				'invoices.updated_at'
			])
			->with([
				'company:id,company_name,company_number',
				'products:id,invoice_id,product_name,quantity,credits,total_credits,credit_price,netto_total,tax'
			]);

		if (!empty($filters['sortBy']) && !empty($filters['sortOrder'])) {
			$query = $this->applySortingBeforePagination($query, $filters['sortBy'], $filters['sortOrder']);
		}

		$query->when(!$isAdmin, fn($q) => $q->where('company_id', $filters['companyId'] ?? null))
			->when($filters['status'] ?? null, fn($q, $status) => $q->where('invoices.status', $status))
			->when($filters['isReference'] ?? false, fn($q) => $q->whereNotNull('invoice_number'))
			->when($filters['invoiceType'] ?? null, fn($q, $type) => $q->where('invoice_type', $type))
			->when($filters['company'] ?? null, fn($q, $company) => $q->where('company_id', $company));

		return $query->filter(request()->only('search', 'status'))
			->where('netto', '!=', 0)
			->paginate($filters['perPage'] ?? 25);
	}

	public function storeInvoice(array $data): Invoice
	{
		return DB::transaction(function () use ($data) {
			$invoice = Invoice::create($data);

			if (!empty($data['products'])) {
				$this->createProducts($data['products'], $invoice);
			}

			return $invoice->load(['company:id,company_name,company_number', 'products']);
		});
	}

	public function updateInvoice(string $id, array $data): Invoice
	{
		return DB::transaction(function () use ($id, $data) {
			$invoice = Invoice::findOrFail($id);
			$invoice->update($data);

			if (!empty($data['products'])) {
				$invoice->products()->delete();
				$this->createProducts($data['products'], $invoice);
			}

			if ($invoice->status !== 'draft' && !$invoice->invoice_number) {
				$invoice->invoice_number = $this->invoiceNumberGenerator->generateInvoiceNumber($invoice);
				$invoice->save();
			}

			return $invoice->load(['company:id,company_name,company_number', 'products']);
		});
	}

	private function createProducts(array $products, Invoice $invoice): void
	{
		$productsArray = array_map(function ($product) {
			return [
				'product_name' => $product['productName'],
				'tax' => $product['tax'],
				'quantity' => $product['quantity'],
				'credits' => $product['credits'],
				'total_credits' => $product['totalCredits'],
				'credit_price' => $product['creditPrice'],
				'product_price' => $product['nettoTotal'],
				'netto_total' => $product['nettoTotal'],
			];
		}, $products);

		$invoice->products()->createMany($productsArray);
	}
}