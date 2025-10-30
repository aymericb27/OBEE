<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    protected $table = "programme";
    protected $fillable = [
        'code',
        'name',
        'ects',
        'semestre',
    ];

    public function ues()
    {
        return $this->belongsToMany(
            UniteEnseignement::class,
            'ue_programme',       // table pivot
            'fk_programme',       // clé étrangère du programme
            'fk_ue'               // clé étrangère de l'UE
        );
    }
}
