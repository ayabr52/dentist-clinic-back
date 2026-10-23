<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function patient(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'api']);
            $user->assignRole('patient');
        });
    }

    public function doctor(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
            $user->assignRole('doctor');
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
            $user->assignRole('admin');
        });
    }
}
