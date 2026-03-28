<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUniversity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UEAnomalie extends Model
{
    use BelongsToUniversity;
    use HasFactory;

    protected $table = 'anomalies';

    protected $fillable = [
        'ue_id',
        'program_id',
        'semester_id',
        'university_id',
        'code',
        'severity',
        'message',
        'details',
        'is_resolved',
        'detected_at',
    ];

    protected $casts = [
        'details' => 'array',
        'is_resolved' => 'boolean',
        'detected_at' => 'datetime',
    ];

    public function ue()
    {
        return $this->belongsTo(UniteEnseignement::class, 'ue_id');
    }

    public function program()
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }

    public function semester()
    {
        return $this->belongsTo(Pro_semester::class, 'semester_id');
    }
}
