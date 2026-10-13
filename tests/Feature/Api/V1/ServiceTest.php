<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الأدوار الضرورية لفحص الصلاحيات
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'api']);
    }

    public function test_anyone_can_view_active_services()
    {
        Service::factory()->create(['is_active' => true, 'name' => 'فحص عام']);
        Service::factory()->create(['is_active' => false, 'name' => 'خدمة معطلة']);

        $response = $this->getJson('/api/v1/services');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data') // لازم تظهر بس الخدمة المفعلة
                 ->assertJsonFragment(['name' => 'فحص عام']);
    }

    public function test_admin_can_create_a_service()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/services', [
            'name' => 'تقويم أسنان',
            'description' => 'تقويم معدني متطور',
            'price' => 150000.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => ['id', 'name', 'price']
                 ]);

        $this->assertDatabaseHas('services', [
            'name' => 'تقويم أسنان'
        ]);
    }

    public function test_admin_can_update_a_service()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $service = Service::factory()->create([
            'name' => 'تنزيل جير',
            'price' => 50000.00
        ]);

        $response = $this->actingAs($admin)->putJson("/api/v1/services/{$service->id}", [
            'name' => 'تنظيف وتلميع الأسنان',
            'description' => 'تنظيف شامل',
            'price' => 75000.00,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'تنظيف وتلميع الأسنان',
            'price' => 75000.00
        ]);
    }
}
