<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        // الأدمن وموظف الاستقبال يمكنهم رؤية أي دفعة
        if ($user->hasRole(['admin', 'receptionist'])) {
            return true;
        }

        // المريض يمكنه رؤية دفعاته الخاصة فقط عبر الفاتورة المرتبطة
        if ($user->hasRole('patient')) {
            return $payment->invoice && $payment->invoice->patient_id === $user->id;
        }

        // الطبيب يمكنه رؤية الدفعات المرتبطة بعيادته أو فواتيره
        if ($user->hasRole('doctor')) {
            return true;
        }

        return false;
    }
}
