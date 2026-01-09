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

    public function prerequis()
    {
        return $this->belongsToMany(
            UniteEnseignement::class,
            'aavue_prerequis',
            'fk_acquis_apprentissage_prerequis',
            'fk_unite_enseignement',
        );
    }
    public function aavvise()
    {
        return $this->belongsToMany(UniteEnseignement::class, 'aavue_vise', 'fk_acquis_apprentissage_vise', 'fk_unite_enseignement')
            ->withTimestamps();
    }

    public function aat()
    {
        return $this->belongsTo(AcquisApprentissageTerminaux::class, 'fk_AAT');
    }
}
