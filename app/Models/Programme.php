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
            'ue_programme',       // table pivot
            'fk_programme',       // clé étrangère du programme
            'fk_unite_enseignement'               // clé étrangère de l'UE
        )->withPivot(['semester', 'university_id']);
    }
}
