<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentStatusRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of the payments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::with(['invoice.appointments', 'invoice.patient', 'collector']);

        if ($user->hasRole('patient')) {
            $query->whereHas('invoice', function ($q) use ($user) {
                $q->where('patient_id', $user->id);
            });
        } else {
            if ($request->filled('patient_id')) {
                $query->whereHas('invoice', function ($q) use ($request) {
                    $q->where('patient_id', $request->patient_id);
                });
            }
        }

        if ($request->filled('status')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $payments = $query->latest()->paginate(10);

        // Modified: Use the unified response macro for the collection of payments
        return response()->success(
            PaymentResource::collection($payments),
            'تم جلب قائمة المدفوعات بنجاح'
        );
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->createPayment($request->validated());

        return response()->success(
            new PaymentResource($payment),
            'تم إنشاء عملية الدفع بنجاح',
            201
        );
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        // Modified: Use the unified response macro for a single payment resource
        return response()->success(
            new PaymentResource($payment->load(['invoice.appointments', 'invoice.patient', 'collector'])),
            'تم جلب تفاصيل الدفع بنجاح'
        );
    }

    /**
     * Update the specified payment status.
     */
    public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment): JsonResponse
    {
        $updatedPayment = $this->paymentService->updateStatus(
            $payment,
            $request->validated()['status']
        );

        return response()->success(
            new PaymentResource($updatedPayment),
            'تم تحديث حالة الدفع بنجاح'
        );
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->success(null, 'تم حذف السجل المالي بنجاح');
    }
}
