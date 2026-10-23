<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    /**
     * Determine whether the user can view any medical records.
     */
    public function viewAny(User $user): bool
    {
        // السماح للأدمن، الطبيب، والمريض باستعراض السجلات (والسيرفس سيهتم بالفلترة)
        return $user->hasRole(['admin', 'doctor', 'patient']);
    }

    /**
     * Determine whether the user can create medical records.
     */
    public function create(User $user): bool
    {
        // السماح فقط للأطباء والآدمن بإنشاء السجلات الطبية
        return $user->hasRole(['doctor', 'admin']);
    }

    /**
     * Determine whether the user can view the specific medical record.
     */
    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        // الآدمن، أو الطبيب مالك السجل، أو المريض صاحب السجل يمكنهم عرضه
        return $user->hasRole('admin') ||
            $user->id === $medicalRecord->doctor_id ||
            $user->id === $medicalRecord->patient_id;
    }

    /**
     * Determine whether the user can update the medical record.
     */
    public function update(User $user, MedicalRecord $medicalRecord): bool
    {
        // السماح للآدمن أو للطبيب الذي أنشأ السجل بتعديله فقط
        return $user->hasRole('admin') || $user->id === $medicalRecord->doctor_id;
    }

    /**
     * Determine whether the user can delete the medical record.
     */
    public function delete(User $user, MedicalRecord $medicalRecord): bool
    {
        // الحذف مقتصر على الآدمن لضمان سلامة البيانات الطبية
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the medical record.
     */
    public function restore(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the medical record.
     */
    public function forceDelete(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole('admin');
    }
}
