<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

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
        // الأدمن أو الطبيب المسؤول عن الموعد يمكنهم تعديله
        if ($user->hasRole('admin') || $user->id === $appointment->doctor_id) {
            return true;
        }

        // المريض يمكنه تعديل/إلغاء موعده فقط إذا كان لا يزال في حالة "معلق" أو "مؤكد" وقبل موعدها
        if ($user->hasRole('patient') && $user->id === $appointment->patient_id) {
            return in_array($appointment->status, ['pending', 'confirmed']);
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
