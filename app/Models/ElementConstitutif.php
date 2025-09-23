<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementConstitutif extends Model
{
    use HasFactory;
    protected $table = 'element_constitutif';

        protected $fillable = [
        'name',
        'description',
        'code',
        'fk_unite_enseignement',
        'volume_horaire',
    ];
}
