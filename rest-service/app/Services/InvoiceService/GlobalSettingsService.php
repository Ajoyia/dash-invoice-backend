<?php

namespace App\Services\InvoiceService;

use App\Models\GlobalSetting;
use App\Services\InvoiceService\Interfaces\GlobalSettingsServiceInterface;
use Exception;
use Illuminate\Http\Request;

class GlobalSettingsService implements GlobalSettingsServiceInterface
{
    public function documentAssignmentList()
    {
        $model = GlobalSetting::where("key", "LIKE", "invoiceTemplateId")
            ->orWhere("key", "LIKE", "invoiceCorrectionTemplateId")
            ->orWhere("key", "LIKE", "invoiceStornoTemplateId")
            ->orWhere("key", "LIKE", "paymentHistoryTemplateId");

        $model = $model->get();

        $response = [];
        foreach ($model as $item) {
            $response[$item->key] = $item->value;
        }

        return response()->json([
            'data' => $response
        ]);
    }

    public function documentAssignmentSave($request)
    {
        $request->validate([
            "invoiceTemplateId" => "required",
            "invoiceCorrectionTemplateId" => "required",
            "invoiceStornoTemplateId" => "required",
            'paymentHistoryTemplateId' => "required"
        ]);

        try {
            $model = GlobalSetting::firstOrNew(['key' => 'invoiceTemplateId']);
            $model->value = $request->invoiceTemplateId;
            $model->save();

            $model = GlobalSetting::firstOrNew(['key' => 'invoiceCorrectionTemplateId']);
            $model->value = $request->invoiceCorrectionTemplateId;
            $model->save();

            $model = GlobalSetting::firstOrNew(['key' => 'invoiceStornoTemplateId']);
            $model->value = $request->invoiceStornoTemplateId;
            $model->save();

            $model = GlobalSetting::firstOrNew(['key' => 'paymentHistoryTemplateId']);
            $model->value = $request->paymentHistoryTemplateId;
            $model->save();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Record Saved."
        ]);
    }
}
