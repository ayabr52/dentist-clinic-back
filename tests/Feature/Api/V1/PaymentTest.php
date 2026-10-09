<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service; // تأكدي من استيراد موديل الخدمة
use PHPUnit\Framework\Attributes\Test;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // إنشاء الطبيب والخدمة لضمان وجود المفاتيح الأجنبية
        $doctor = User::factory()->create(); // أو Role/Doctor Factory إذا كان لديكِ
        $service = Service::factory()->create();

        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id'  => $doctor->id,
            'service_id' => $service->id,
            'status'     => 'confirmed',
        ]);
    }

    #[Test]
    public function it_can_create_a_payment_record()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments', [
                'appointment_id' => $this->appointment->id,
                'patient_id'     => $this->user->id,
                'amount'         => 150.00,
                'discount'       => 20.00,
                'payment_method' => 'cash',
                'status'         => 'pending',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'final_amount' => 130.00,
                    'status'       => 'pending',
                ]
            ]);

        $this->assertDatabaseHas('payments', [
            'appointment_id' => $this->appointment->id,
            'final_amount'   => 130.00,
        ]);
    }

    #[Test]
    public function it_can_update_payment_status()
    {
        $payment = Payment::create([
            'appointment_id' => $this->appointment->id,
            'patient_id'     => $this->user->id,
            'amount'         => 100.00,
            'discount'       => 0.00,
            'final_amount'   => 100.00,
            'status'         => 'pending',
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/payments/{$payment->id}/status", [
                'status' => 'paid',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'status' => 'paid',
                ]
            ]);

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'paid',
        ]);
    }
}
