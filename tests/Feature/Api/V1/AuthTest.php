<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الأدوار الأساسية لتعمل مع حزمة Spatie أثناء التيست
        Role::create(['name' => 'patient']);
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'receptionist']);
    }

    public function test_it_can_register_a_new_user_successfully()
    {
        $response = $this->postJson('/api/v1/register', [
            'first_name' => 'آيه',
            'last_name' => 'برغوث',
            'email' => 'aya@example.com',
            'phone' => '0999999999',
            'password' => 'password123',
            'role' => 'patient',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'user',
                         'token'
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'aya@example.com'
        ]);
    }

    public function test_it_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->assignRole('patient');

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'user',
                         'token'
                     ]
                 ]);
    }
}
