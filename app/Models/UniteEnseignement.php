<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniteEnseignement extends Model

{
    use HasFactory;

    protected $table = "unite_enseignement";
    protected $fillable = [
        'name',
        'description',
        'code',
        'ects',
    ];

    public function aavvise()
    {
        return $this->belongsToMany(AcquisApprentissageVise::class, 'aavue_vise', 'fk_unite_enseignement', 'fk_acquis_apprentissage_vise');
    }

    public function aavprerequis()
    {
        return $this->belongsToMany(AcquisApprentissageVise::class, 'aavue_prerequis', 'fk_unite_enseignement', 'fk_acquis_apprentissage_prerequis');
    }
    public function prerequis()
    {
        return $this->belongsToMany(
            AcquisApprentissageVise::class,
            'aavue_prerequis',
            'fk_unite_enseignement',
            'fk_acquis_apprentissage_prerequis'
        )->select('acquis_apprentissage_vise.id', 'code');
    }

    public function vise()
    {
        return $this->belongsToMany(
            AcquisApprentissageVise::class,
            'aavue_vise',
            'fk_unite_enseignement',
            'fk_acquis_apprentissage_vise'
        )->select('acquis_apprentissage_vise.id', 'code');
    }
}
