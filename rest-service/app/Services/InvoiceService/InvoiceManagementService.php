<?php

namespace App\Services\InvoiceService;

use App\Repositories\InvoiceRepositoryInterface;
use App\Models\Invoice;
use App\Services\InvoiceService\Interfaces\InvoiceManagementServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class InvoiceManagementService extends AbstractInvoiceService implements InvoiceManagementServiceInterface
{
    public function getServiceName(): string
    {
        return 'InvoiceManagementService';
    }

    public function storeInvoice(array $data): Invoice
    {
        return $this->invoiceRepository->storeInvoice($data);
    }

    public function updateInvoice(string $id, array $data): Invoice
    {
        return $this->invoiceRepository->updateInvoice($id, $data);
    }

    public function deleteInvoice(string $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }

        $invoice->delete();

        return response()->json(['message' => 'Record deleted.'], 200);
    }

    public function updateInvoiceStatus($request, $id)
    {
        $request->validate([
            "status" => "required|in:approved,sent,paid,warning level 1,warning level 2,warning level 3",
        ]);

        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status != 'draft') {
                $invoice->status = $request->status;
                if ($request->status == 'paid') {
                    $invoice->paid_at = Carbon::now();
                }
            }

            $invoice->save();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Invoice status has been updated"
        ]);
    }
}
