<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DoctorScheduleAndLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // استخدام الـ State الصحيح لتعيين دور الطبيب عبر Spatie
        $this->doctor = User::factory()->doctor()->create();
    }

    #[Test]
    public function it_can_create_a_doctor_schedule()
    {
        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson('/api/v1/doctor-schedules', [
                'doctor_id' => $this->doctor->id, // إضافة doctor_id خارج أو داخل المصفوفة حسب الـ Validation لديك
                'schedules' => [
                    [
                        'day_of_week' => 1,
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                        'is_working_day' => true,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'تم حفظ جدول أوقات العمل بنجاح',
            ]);

        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'is_working_day' => 1,
        ]);
    }

    #[Test]
    public function it_validates_schedule_creation()
    {
        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson('/api/v1/doctor-schedules', [
                'doctor_id' => $this->doctor->id,
                'schedules' => [
                    [
                        'day_of_week' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'schedules.0.start_time',
                'schedules.0.end_time',
            ]);
    }

    #[Test]
    public function it_can_create_a_doctor_leave()
    {
        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson('/api/v1/doctor-leaves', [
                'doctor_id' => $this->doctor->id,
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-15',
                'reason' => 'إجازة سنوية',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'تم تسجيل الإجازة بنجاح',
            ]);

        $this->assertDatabaseHas('doctor_leaves', [
            'doctor_id' => $this->doctor->id,
            'reason' => 'إجازة سنوية',
        ]);
    }

    #[Test]
    public function it_can_delete_a_doctor_leave()
    {
        $leaveId = DB::table('doctor_leaves')->insertGetId([
            'doctor_id' => $this->doctor->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->deleteJson("/api/v1/doctor-leaves/{$leaveId}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Doctor leave deleted successfully.',
            ]);

        $this->assertDatabaseMissing('doctor_leaves', [
            'id' => $leaveId,
        ]);
    }

    public function test_can_get_available_slots_for_doctor()
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        // اختيار يوم غد للتأكد من اجتياز شرط after_or_equal:today
        $tomorrow = now()->addDay();
        $dayOfWeek = $tomorrow->dayOfWeek;

        // إنشاء جدول دوام للطبيب في هذا اليوم
        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'is_working_day' => true,
        ]);

        $response = $this->getJson("/api/v1/doctors/{$doctor->id}/available-slots?date=".$tomorrow->toDateString());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    public function test_validate_date_is_required_for_available_slots()
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $response = $this->getJson("/api/v1/doctors/{$doctor->id}/available-slots");

        $response->assertStatus(422); // يجب أن يعود بخطأ تحقق لأن التاريخ مفقود
    }
}
