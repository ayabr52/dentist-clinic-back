<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User; // استدعاء مودل المواعيد
use App\Services\AiDiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MedicalRecordAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_create_medical_record_with_ai_analysis()
    {
        // 1. تجهيز بيئة التخزين الوهمية لقرص private
        Storage::fake('private');

        // 2. إنشاء الأدوار المطلوبة في قاعدة بيانات الاختبار
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'api']);

        // 3. إنشاء الطبيب والمريض كـ Users وإسناد الأدوار إليهما
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $patient = User::factory()->create();
        $patient->assignRole('patient');

        // 4. إنشاء موعد تجريبي مرتبط بالمريض والطبيب لتجاوز التحقق وقاعدة البيانات
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        // 5. عمل Mock لخدمة الذكاء الاصطناعي بحيث تعيد نتيجة ناجحة افتراضية
        $this->mock(AiDiagnosticService::class, function ($mock) {
            $mock->shouldReceive('analyze')
                ->once()
                ->andReturn(json_encode([
                    'status' => 'success',
                    'preliminary_finding' => 'Test AI Analysis Result',
                ]));
        });

        // 6. إرسال طلب POST لإنشاء السجل الطبي مع إضافة appointment_id والصورة الوهمية
        $response = $this->actingAs($doctor, 'sanctum')->postJson('/api/v1/medical-records', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id, // تمرير معرف الموعد الإجباري
            'diagnosis' => 'Dental Caries Test',
            'images' => [
                UploadedFile::fake()->create('tooth_xray.jpg', 100, 'image/jpeg'),
            ],
        ]);

        // 7. التحقق من نجاح الاستجابة وحفظ البيانات في قاعدة البيانات
        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'diagnosis' => 'Dental Caries Test',
        ]);
    }
}
