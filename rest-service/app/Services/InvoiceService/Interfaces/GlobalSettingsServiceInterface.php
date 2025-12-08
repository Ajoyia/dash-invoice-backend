<?php

namespace App\Services\InvoiceService\Interfaces;

use Illuminate\Http\Request;

interface GlobalSettingsServiceInterface
{
    public function documentAssignmentList();

    public function documentAssignmentSave(Request $request);
}
