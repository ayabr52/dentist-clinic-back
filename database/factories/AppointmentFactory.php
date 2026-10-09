<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Service; // تأكدي من مسار الموديل الخاص بالخدمات
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'doctor_id' => User::factory(),
            'service_id' => Service::factory(),
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '10:00',
            'status' => 'confirmed',
        ];
    }
}
