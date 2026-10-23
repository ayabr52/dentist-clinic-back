<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'price', 'duration_minutes', 'is_active'];

    // الأطباء الذين يقدمون هذه الخدمة
    public function doctors()
    {
        return $this->belongsToMany(User::class, 'doctor_services', 'service_id', 'doctor_id')
            ->withPivot('custom_price')
            ->withTimestamps();
    }

    // المواعيد المرتبطة بهذه الخدمة
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
