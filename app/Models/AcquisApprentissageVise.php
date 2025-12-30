<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcquisApprentissageVise extends Model
{
    use HasFactory;
    protected $table = "acquis_apprentissage_vise";
    protected $fillable = [
        'description',
        'name',
        'code',
        'fk_AAT',
        'contribution',
    ];

    public function aats()
    {
        return $this->belongsToMany(
            AcquisApprentissageTerminaux::class,
            'aav_aat',
            'fk_aav',
            'fk_aat'
        )->withPivot('contribution')
            ->withTimestamps();
    }


    public function aat()
    {
        return $this->belongsTo(AcquisApprentissageTerminaux::class, 'fk_AAT');
    }
}
