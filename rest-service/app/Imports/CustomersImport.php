<?php

namespace App\Imports;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

class CustomersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $existingCompany = Company::where('company_name', $row['company_name'])->first();
        if (!$existingCompany) {
            $companyData = [
                'company_name' => $row['company_name'],
                'vat_id' => $row['vat_id'],
                'address_line_1' => $row['address_line_1'],
                'address_line_2' => $row['address_line_2'],
                'city' => $row['city'],
                'country' => $row['country'],
                'zip_code' => $row['zip_code'],
                'phone' => $row['phone'],
                'credits' => $row['credits'],
                'invoice_email' => $row['invoice_email'],
                'warning_invoice_email' => $row['warning_email_address'],
                'apply_reverse_charge' => $row['apply_reverse_charge'],
                'external_order_number' => $row['external_order_number'],
            ];

            $company = new Company($companyData);
            $company->save();

            $companyNumber = DB::table('companies')->max(DB::raw("CAST(SUBSTRING(company_number, 2) AS UNSIGNED)")) ?? 1000;
            $company->company_number = 'C' . ($companyNumber + 1);
            $company->save();
        }
    }
}
