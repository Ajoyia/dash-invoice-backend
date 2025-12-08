<?php

namespace App\Services\InvoiceService;

use App\Models\GlobalSetting;
use App\Services\InvoiceService\Interfaces\GlobalSettingsServiceInterface;
use Exception;
use Illuminate\Http\Request;

class GlobalSettingsService implements GlobalSettingsServiceInterface
{
    private const TEMPLATE_KEYS = [
        'invoiceTemplateId',
        'invoiceCorrectionTemplateId',
        'invoiceStornoTemplateId',
        'paymentHistoryTemplateId',
    ];

    public function documentAssignmentList()
    {
        $settings = GlobalSetting::whereIn('key', self::TEMPLATE_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        return response()->json([
            'data' => $settings,
        ]);
    }

    public function documentAssignmentSave(Request $request)
    {
        $request->validate([
            'invoiceTemplateId' => 'required|string',
            'invoiceCorrectionTemplateId' => 'required|string',
            'invoiceStornoTemplateId' => 'required|string',
            'paymentHistoryTemplateId' => 'required|string',
        ]);

        try {
            $settings = [
                'invoiceTemplateId' => $request->invoiceTemplateId,
                'invoiceCorrectionTemplateId' => $request->invoiceCorrectionTemplateId,
                'invoiceStornoTemplateId' => $request->invoiceStornoTemplateId,
                'paymentHistoryTemplateId' => $request->paymentHistoryTemplateId,
            ];

            foreach ($settings as $key => $value) {
                GlobalSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Record Saved.',
        ]);
    }
}
