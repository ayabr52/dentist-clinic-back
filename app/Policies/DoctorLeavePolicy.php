<?php

namespace App\Policies;

use App\Models\DoctorLeave;
use App\Models\User;

class DoctorLeavePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'api') || $user->hasRole('doctor', 'api');
    }

    public function update(User $user, DoctorLeave $leave): bool
    {
        return $user->hasRole('admin', 'api') || $user->id === $leave->doctor_id;
    }

    public function delete(User $user, DoctorLeave $leave): bool
    {
        return $user->hasRole('admin', 'api') || $user->id === $leave->doctor_id;
    }
}
