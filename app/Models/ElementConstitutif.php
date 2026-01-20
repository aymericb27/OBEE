<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementConstitutif extends Model
{
    protected $table = "element_constitutif";
    protected $fillable = [
        'fk_ue_parent',
        'fk_ue_child',
        'contribution',
        'university_id',
    ];
}
