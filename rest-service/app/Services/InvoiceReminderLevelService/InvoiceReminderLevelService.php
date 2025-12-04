<?php

namespace App\Services\InvoiceReminderLevelService;

use App\Models\InvoiceReminderLevel;

class InvoiceReminderLevelService
{

    /**
     * Store a new invoice_reminder_level.
     *
     * @param array $data The data for the new invoice_reminder_level.
     * @return InvoiceReminderLevel The created invoice_reminder_level.
     */
    public function storeInvoiceReminderLevel(array $data)
    {
        // Validate and create the invoice_reminder_level
        $invoice_reminder_level = InvoiceReminderLevel::create($data);
        $invoice_reminder_level->save();
        return $invoice_reminder_level;
    }

    /**
     * Update an existing invoice_reminder_level.
     *
     * @param string $id The ID of the invoice_reminder_level to update.
     * @param array $data The updated data for the invoice_reminder_level.
     * @return InvoiceReminderLevel The updated invoice_reminder_level.
     */
    public function updateInvoiceReminderLevel(string $id, array $data)
    {
        // Find the invoice_reminder_level
        $invoice_reminder_level = InvoiceReminderLevel::findOrFail($id);

        // Find or create the invoice_reminder_level by level_name
        $invoice_reminder_level = InvoiceReminderLevel::firstOrNew(['level_name' => $data['level_name']]);

        $invoice_reminder_level->fill($data);

        $invoice_reminder_level->save();

        return $invoice_reminder_level;
    }

    public function deleteInvoiceReminderLevel($id)
    {
        $company = InvoiceReminderLevel::find($id);

        if (!$company) {
            return response()->json(['error' => 'Invoice Reminder Level not found.'], 404);
        }

        $company->delete();

        return response()->json(['message' => 'Record deleted.'], 200);
    }
}