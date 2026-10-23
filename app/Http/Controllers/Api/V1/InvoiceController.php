<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of all invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['patient', 'appointments', 'payments'])->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }

    /**
     * Display the specified invoice details.
     */
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with(['patient', 'appointments', 'payments.collector'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $invoice,
        ]);
    }
}
