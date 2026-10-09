<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorLeave extends Model
{
    protected $fillable = [
        'doctor_id',
        'start_date',
        'end_date',
        'reason',
    ];

    /**
     * الطبيب صاحب الإجازة (مرتبط بموديل User)
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
