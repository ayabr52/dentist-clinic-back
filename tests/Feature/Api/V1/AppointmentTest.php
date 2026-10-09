<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Carbon\Carbon;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'patient']);
        Role::firstOrCreate(['name' => 'doctor']);
    }

    public function test_patient_can_book_an_appointment()
    {
        $patient = User::factory()->create();
        $patient->assignRole('patient');

        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $service = Service::factory()->create();

        $appointmentDate = now()->addDay();
        $dayOfWeek = $appointmentDate->dayOfWeek;

        // إنشاء جدول دوام للطبيب يغطي وقت الاختبار
        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
           'is_working_day' => true,
        ]);

        $response = $this->actingAs($patient)->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'start_time' => '10:00',
            'notes' => 'موعد لفحص الأسنان',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'doctor_id', 'patient_id', 'status']
            ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'start_time' => '10:00',
        ]);
    }

    public function test_cannot_book_conflicting_appointment_for_same_doctor()
    {
        $patient1 = User::factory()->create();
        $patient2 = User::factory()->create();
        $doctor = User::factory()->create();
        $service = Service::factory()->create();

        $appointmentDate = now()->addDay();
        $date = $appointmentDate->format('Y-m-d');
        $dayOfWeek = $appointmentDate->dayOfWeek;
        $time = '11:30';

        // إنشاء جدول دوام للطبيب يغطي هذا الوقت
        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
           'is_working_day' => true,
        ]);

        // الحجز الأول
        Appointment::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => $time,
            'status' => 'pending'
        ]);

        // محاولة الحجز بنفس الموعد تماماً لنفس الطبيب بواسطة مريض آخر
        $response = $this->actingAs($patient2)->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => $time,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'عذراً، هذا الموعد محجوز مسبقاً لهذا الطبيب.'
            ]);
    }
}
