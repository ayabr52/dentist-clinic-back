<?php

namespace App\Models;

use Illuminate\Database\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'image_path',
        'image_type',
        'title',
    ];

    /**
     * العلاقة: الصورة تتبع لسجل طبي واحد.
     */
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
