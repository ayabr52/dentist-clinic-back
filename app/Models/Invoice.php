<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'total_amount',
        'discount',
        'final_amount',
        'paid_amount',
        'status', // unpaid, partial, paid
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
