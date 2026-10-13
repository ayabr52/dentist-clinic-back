<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

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
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'تم إضافة جدول الدوام بنجاح',
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
                'day_of_week' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time', 'is_working_day']);
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
                'message' => 'تم حذف الإجازة بنجاح',
            ]);

        $this->assertDatabaseMissing('doctor_leaves', [
            'id' => $leaveId,
        ]);
    }
}
