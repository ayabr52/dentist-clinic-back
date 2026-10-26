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

        // الطبيب يمكنه رؤية الدفعات المرتبطة بمواعيده أو فواتيره الخاصة فقط
        if ($user->hasRole('doctor')) {
            return $payment->invoice &&
                   $payment->invoice->appointment &&
                   $payment->invoice->appointment->doctor_id === $user->id;
        }

        // المريض يمكنه رؤية دفعاته الخاصة فقط
        if ($user->hasRole('patient')) {
            return $payment->invoice && $payment->invoice->patient_id === $user->id;
        }

        return false;
    }
}
