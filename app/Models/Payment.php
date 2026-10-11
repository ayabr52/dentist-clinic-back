<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method',
        'collected_by',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // للوصول للموعد والمريض مباشرة بكل سهولة عبر الفاتورة:
    public function appointment()
    {
        return $this->hasOneThrough(Appointment::class, Invoice::class, 'id', 'id', 'invoice_id', 'appointment_id');
    }
}
