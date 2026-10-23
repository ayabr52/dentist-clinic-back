<?php

namespace App\Policies;

use App\Models\DoctorLeave;
use App\Models\User;

class DoctorLeavePolicy
{
    public function update(User $user, DoctorLeave $leave): bool
    {
        return $user->hasRole('admin', 'api') || $user->id === $leave->doctor_id;
    }

    public function delete(User $user, DoctorLeave $leave): bool
    {
        return $user->hasRole('admin', 'api') || $user->id === $leave->doctor_id;
    }
}
