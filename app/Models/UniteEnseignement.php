<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToUniversity;

class UniteEnseignement extends Model

{
    use BelongsToUniversity;

    use HasFactory;

    protected $table = "unite_enseignement";
    protected $fillable = [
        'name',
        'description',
        'code',
        'ects',
        'semestre',
        'university_id',
    ];
    public function children()
    {
        return $this->belongsToMany(
            UniteEnseignement::class,
            'element_constitutif',
            'fk_ue_parent',
            'fk_ue_child'
        );
    }

    public function parent()
    {
        return $this->belongsToMany(
            UniteEnseignement::class,
            'element_constitutif',
            'fk_ue_child',
            'fk_ue_parent'
        );
    }

    public function pro()
    {
        return $this->belongsToMany(Programme::class, 'ue_programme', 'fk_unite_enseignement', 'fk_programme')
            ->withPivot('semester')
            ->withTimestamps();
    }
    public function aat()
    {
        return $this->belongsToMany(AcquisApprentissageTerminaux::class, 'ue_aat', 'fk_ue', 'fk_aat')
            ->withPivot('contribution')
            ->withTimestamps();
    }

    public function aavvise()
    {
        return $this->belongsToMany(AcquisApprentissageVise::class, 'aavue_vise', 'fk_unite_enseignement', 'fk_acquis_apprentissage_vise')
            ->withTimestamps();
    }

    public function prerequis()
    {
        return $this->belongsToMany(
            AcquisApprentissageVise::class,
            'aavue_prerequis',
            'fk_unite_enseignement',
            'fk_acquis_apprentissage_prerequis'
        );
    }
}
