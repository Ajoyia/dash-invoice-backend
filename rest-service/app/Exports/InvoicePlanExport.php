<?php

namespace App\Exports;

use App\Models\Invoice;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InvoicePlanExport implements FromArray, WithCustomStartCell, WithEvents, WithHeadings
{
    private const COLUMNS_PER_MONTH = 3;

    private const MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    protected $companyId;

    protected $isAdmin;

    protected $currentYear;

    protected $currentMonth;

    public function __construct($companyId, $isAdmin)
    {
        $this->companyId = $companyId;
        $this->isAdmin = $isAdmin;
        $now = Carbon::now();
        $this->currentYear = $now->year;
        $this->currentMonth = $now->month;
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function array(): array
    {
        $query = Invoice::query()
            ->with('company:id,company_name')
            ->where('status', '!=', 'draft')
            ->whereYear('created_at', $this->currentYear);

        if (! $this->isAdmin) {
            $query->where('company_id', $this->companyId);
        }

        $invoices = $query->get();
        $invoicesData = [];

        foreach ($invoices as $invoice) {
            $data = $this->buildBaseData($invoice);
            $monthlyData = $this->calculateMonthlyData($invoice);
            $invoicesData[] = array_merge($data, $monthlyData);
        }

        return $invoicesData;
    }

    private function buildBaseData(Invoice $invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->company->company_name ?? '',
            $invoice->due_date,
            $invoice->netto,
            $invoice->total_amount,
            $invoice->status,
        ];
    }

    private function calculateMonthlyData(Invoice $invoice): array
    {
        $dueDateMonth = Carbon::parse($invoice->due_date)->month;
        $totalColumns = 12 * self::COLUMNS_PER_MONTH;
        $monthlyData = array_fill(0, $totalColumns, '');

        if ($invoice->status !== 'paid') {
            $this->addUnpaidInvoiceData($monthlyData, $dueDateMonth, $invoice->total_amount);
        } else {
            $paidAtMonth = Carbon::parse($invoice->paid_at)->month;
            $this->addPaidInvoiceData($monthlyData, $dueDateMonth, $paidAtMonth, $invoice->total_amount);
        }

        return $monthlyData;
    }

    private function addUnpaidInvoiceData(array &$monthlyData, int $dueDateMonth, float $totalAmount): void
    {
        if ($dueDateMonth >= $this->currentMonth) {
            $position = ($dueDateMonth * self::COLUMNS_PER_MONTH) - self::COLUMNS_PER_MONTH;
            if ($position >= 0 && $position < count($monthlyData)) {
                $monthlyData[$position] = $totalAmount;
            }
        }
    }

    private function addPaidInvoiceData(array &$monthlyData, int $dueDateMonth, int $paidAtMonth, float $totalAmount): void
    {
        if ($dueDateMonth !== $paidAtMonth) {
            $position = ($paidAtMonth * self::COLUMNS_PER_MONTH) - (self::COLUMNS_PER_MONTH - 1);
        } else {
            $position = ($paidAtMonth * self::COLUMNS_PER_MONTH) - 1;
        }

        if ($position >= 0 && $position < count($monthlyData)) {
            $monthlyData[$position] = $totalAmount;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->setupMonthHeaders($event->sheet);
            },
        ];
    }

    private function setupMonthHeaders($sheet): void
    {
        $columnIndex = 7;

        foreach (self::MONTHS as $month) {
            $cellRange = $this->getMonthCellRange($columnIndex);
            $centerCell = $this->getCenterCell($columnIndex);

            $sheet->mergeCells($cellRange);
            $sheet->setCellValue($centerCell, $month);
            $sheet->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $columnIndex += self::COLUMNS_PER_MONTH;
        }
    }

    private function getMonthCellRange(int $columnIndex): string
    {
        $startCol = $this->numberToColumn($columnIndex);
        $endCol = $this->numberToColumn($columnIndex + self::COLUMNS_PER_MONTH - 1);

        return "{$startCol}1:{$endCol}1";
    }

    private function getCenterCell(int $columnIndex): string
    {
        $centerCol = $this->numberToColumn($columnIndex + 1);

        return "{$centerCol}1";
    }

    private function numberToColumn(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)).$column;
            $number = intval($number / 26);
        }

        return $column;
    }

    public function headings(): array
    {
        $headings = [
            'Invoice Number',
            'Customer Name',
            'Due Date',
            'Money Net',
            'Money Gross',
            'Status',
        ];

        $monthColumns = ['Planned', 'Old', 'Current'];
        for ($i = 0; $i < 12; $i++) {
            $headings = array_merge($headings, $monthColumns);
        }

        return $headings;
    }
}
