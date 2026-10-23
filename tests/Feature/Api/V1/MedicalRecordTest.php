<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_create_medical_record()
    {
        // 1. إنشاء طبيب ومريض بالطريقة النظيفة الصحيحة عبر Spatie States
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->patient()->create();

        $service = Service::create([
            'name' => 'General Checkup',
            'price' => 100,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'status' => 'confirmed',
        ]);

        // 2. إرسال طلب إضافة سجل طبي من قبل الطبيب
        $response = $this->actingAs($doctor)->postJson('/api/v1/medical-records', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'diagnosis' => 'التهاب بسيط في الحلق',
            'prescription' => 'بانادول 3 مرات يومياً',
            'doctor_notes' => 'الرجاء الراحة لمدة يومين',
        ]);

        // 3. التحقق من النجاح وحفظه في قاعدة البيانات
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'patient_id', 'doctor_id', 'diagnosis', 'prescription'],
            ]);

        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'التهاب بسيط في الحلق',
        ]);
    }

    public function test_patient_can_view_their_medical_records()
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        // إنشاء سجل طبي للمريض
        MedicalRecord::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'فحص دوري',
        ]);

        // طلب جلب الملفات الطبية للمريض
        $response = $this->actingAs($patient)->getJson('/api/v1/medical-records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'diagnosis', 'doctor'],
                ],
            ]);
    }
}
