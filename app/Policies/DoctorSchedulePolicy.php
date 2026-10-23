<?php

namespace App\Policies;

use App\Models\DoctorSchedule;
use App\Models\User;

class DoctorSchedulePolicy
{
    /**
     * Determine if the user can update or delete the schedule.
     */
    public function update(User $user, DoctorSchedule $schedule): bool
    {
        return $user->hasRole('admin', 'api') || $user->id === $schedule->doctor_id;
    }

    public function delete(User $user, DoctorSchedule $schedule): bool
    {
        return $user->hasRole('admin', 'api') || $user->id === $schedule->doctor_id;
    }
}
