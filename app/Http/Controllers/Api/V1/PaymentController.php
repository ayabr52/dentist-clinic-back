<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentStatusRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of the payments.
     *
     * This method retrieves a list of payments, optionally filtered by patient ID and status.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing optional filters.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the status and the list of payments.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the specified patient ID does not exist.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['appointment', 'patient']);

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    

    public function store(StorePaymentRequest $request)
    {
        $payment = $this->paymentService->createPayment($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء عملية الدفع بنجاح',
            'data' => $payment
        ], 201);
    }

    public function show(Payment $payment)
    {
        return response()->json([
            'status' => 'success',
            'data' => $payment->load(['appointment', 'patient'])
        ]);
    }

    public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment)
    {
        $updatedPayment = $this->paymentService->updateStatus(
            $payment,
            $request->validated()['status'],
            $request->validated()['payment_method'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الدفع بنجاح',
            'data' => $updatedPayment
        ]);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete(); // Soft Delete

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف السجل المالي بنجاح'
        ]);
    }
}
