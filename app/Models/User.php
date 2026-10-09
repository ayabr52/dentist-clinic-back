<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles; // لا تنسي تفعلي حزمة الصلاحيات هنا بالموديل
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Service;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles , HasApiTokens;

    // تحديد الـ Guard الخاص بـ Spatie ليتطابق مع الـ API
    protected string $guard_name = 'api';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'specialization',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code', // لحماية بيانات الـ OTP من التسريب بالـ API responses
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
        ];
    }

    /**
     * Accessor لدمج الاسم الأول واسم العائلة للحصول على الاسم الكامل تلقائياً.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => "{$attributes['first_name']} {$attributes['last_name']}"
        );
    }


    /**
     * الخدمات التي يقدمها الطبيب (Doctor - Services Relationship)
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'doctor_services', 'doctor_id', 'service_id')
                    ->withPivot('custom_price')
                    ->withTimestamps();
    }

    /**
     * إجازات الطبيب
     */
    public function leaves()
    {
        return $this->hasMany(DoctorLeave::class, 'doctor_id');
    }
}
