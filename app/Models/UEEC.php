<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UEEC extends Model
{
    use HasFactory;
    protected $table = "ueec";
    protected $fillable = [
        'fk_unite_enseignement',
        'fk_element_constitutif',
    ];
}
