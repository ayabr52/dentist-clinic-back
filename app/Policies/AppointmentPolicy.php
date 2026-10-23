<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any appointments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'doctor', 'patient']);
    }

    /**
     * Determine whether the user can view the specific appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin') ||
            $user->id === $appointment->doctor_id ||
            $user->id === $appointment->patient_id;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        // المريض أو الأدمن أو موظف الاستقبال يمكنهم إنشاء حجز
        return $user->hasRole(['admin', 'patient', 'doctor']);
    }

    /**
     * Determine whether the user can update/change appointment status.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // الأدمن لديه صلاحية مطلقة
        if ($user->hasRole('admin')) {
            return true;
        }

        // الطبيب يمكنه تعديل مواعيده الخاصة
        if ($user->hasRole('doctor') && $appointment->doctor_id === $user->id) {
            return true;
        }

        // إذا كان المستخدم مريضاً، مسموح له فقط إلغاء موعده الخاص بالشروط الزمنية
        if ($user->hasRole('patient') && $appointment->patient_id === $user->id) {
            // التحقق أن الموعد لم يمر وقته بعد (بمقارنة تاريخ ووقت الموعد بالوقت الحالي)
            $appointmentDateTime = Carbon::parse($appointment->appointment_date.' '.$appointment->start_time);

            // المريض يمكنه فقط إلغاء موعد لم ينقضِ وقته بعد (وليس تعديل أي شيء آخر)
            return $appointmentDateTime->isFuture() && in_array($appointment->status, ['pending', 'confirmed']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete/cancel the appointment.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin') || $user->id === $appointment->patient_id || $user->id === $appointment->doctor_id;
    }
}
