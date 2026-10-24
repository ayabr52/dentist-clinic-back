<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $appointment;

    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $doctor = User::factory()->doctor()->create();
        $service = Service::factory()->create();

        $this->invoice = Invoice::create([
            'patient_id' => $this->user->id,
            'total_amount' => 150.00,
            'discount' => 0.00,
            'final_amount' => 150.00,
            'status' => 'unpaid',
        ]);

        $this->appointment = Appointment::create([
            'patient_id' => $this->user->id,
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'invoice_id' => $this->invoice->id,
            'status' => 'confirmed',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
        ]);
    }

    #[Test]
    public function it_can_create_payment_record_and_update_invoice_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments', [
                'appointment_id' => $this->appointment->id,
                'amount' => 150.00,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201);

        // التحقق من إنشاء سجل الدفع في جدول payments بالأعمدة الحقيقية فقط
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 150.00,
            'payment_method' => 'cash',
            'collected_by' => $this->user->id,
        ]);

        // التحقق من تحديث رصيد وحالة الفاتورة المرتبطة بها تلقائياً
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'paid_amount' => 150.00,
            'status' => 'paid',
        ]);
    }
}
