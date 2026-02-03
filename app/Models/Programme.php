<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToUniversity;

class Programme extends Model
{
    use BelongsToUniversity;

    protected $table = "programme";
    protected $fillable = [
        'code',
        'name',
        'ects',
        'semestre',
        'university_id',

    ];

    public function ues()
    {
        return $this->belongsToMany(
            UniteEnseignement::class,
            'ue_programme',
            'fk_programme',
            'fk_unite_enseignement'
        )->withPivot('fk_semester', 'university_id')
            ->withTimestamps();
    }

    public function prerequis()
    {
        return $this->belongsToMany(
            AcquisApprentissageVise::class,
            'aavpro_prerequis',
            'fk_programme',
            'fk_acquis_apprentissage_prerequis'
        );
    }
}
