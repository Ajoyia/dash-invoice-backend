<?php

namespace App\Exports;

use App\Models\Invoice;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class InvoicePlanExport implements FromArray, WithHeadings, WithCustomStartCell, WithEvents
{
    protected $companyId;
    protected $isAdmin;

    public function __construct($companyId, $isAdmin)
    {
        $this->companyId = $companyId;
        $this->isAdmin = $isAdmin;
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function array(): array
    {
        $invoicesData = [];

        $invoices = new Invoice();
        if (!$this->isAdmin) {
            $invoices = $invoices->where('company_id', $this->companyId);
        }

        $invoices = $invoices->where('status', '!=', 'draft')->whereYear('created_at', Carbon::now()->year)->get();
        foreach ($invoices as $invoice) {
            $data = [
                $invoice->invoice_number,
                $invoice->company->company_name,
                $invoice->due_date,
                $invoice->netto,
                $invoice->total_amount,
                $invoice->status,
            ];
            $dueDateMonth = Carbon::parse($invoice->due_date)->month;
            if ($invoice->status != 'paid') {
                if ($dueDateMonth >= Carbon::now()->month) {
                    for ($i = 1; $i <= ($dueDateMonth * 3) - 3; $i++) {
                        $data[] = "";
                    }
                    $data[] = $invoice->total_amount;
                    array_push($invoicesData, $data);
                    continue;
                }
            }

            if ($invoice->status == 'paid') {
                $paidAtMonth = Carbon::parse($invoice->paid_at)->month;
                if ($dueDateMonth < $paidAtMonth || $dueDateMonth > $paidAtMonth) {
                    for ($j = 1; $j <= ($paidAtMonth * 3) - 2; $j++) {
                        $data[] = "";
                    }
                    $data[] = $invoice->total_amount;
                    array_push($invoicesData, $data);
                } else {
                    for ($j = 1; $j <= ($paidAtMonth * 3) - 1; $j++) {
                        $data[] = "";
                    }
                    $data[] = $invoice->total_amount;
                    array_push($invoicesData, $data);
                }
            }
        }
        return $invoicesData;
    }

    public function registerEvents(): array
    {

        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->mergeCells('G1:I1');
                $sheet->setCellValue('H1', "January");
                //                $sheet->getStyle('G1:I1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('G1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('J1:L1');
                $sheet->setCellValue('K1', "February");

                $sheet->mergeCells('M1:O1');
                $sheet->setCellValue('N1', "March");

                $sheet->mergeCells('P1:R1');
                $sheet->setCellValue('Q1', "April");

                $sheet->mergeCells('S1:U1');
                $sheet->setCellValue('T1', "May");

                $sheet->mergeCells('V1:X1');
                $sheet->setCellValue('W1', "June");

                $sheet->mergeCells('Y1:AA1');
                $sheet->setCellValue('Z1', "July");

                $sheet->mergeCells('AB1:AD1');
                $sheet->setCellValue('AC1', "August");

                $sheet->mergeCells('AE1:AG1');
                $sheet->setCellValue('AF1', "September");

                $sheet->mergeCells('AH1:AJ1');
                $sheet->setCellValue('AI1', "October");

                $sheet->mergeCells('AK1:AM1');
                $sheet->setCellValue('AL1', "November");

                $sheet->mergeCells('AN1:AP1');
                $sheet->setCellValue('AO1', "December");
            },
        ];
    }

    public function headings(): array
    {
        $array = [
            'Invoice Number',
            'Customer Name',
            'Due Date',
            'Money Net',
            'Money Gross',
            'Status',
        ];

        for ($i = 1; $i <= 12; $i++) {
            $array[] = 'Planned';
            $array[] = 'Old';
            $array[] = 'Current';
        }
        return $array;
    }
}