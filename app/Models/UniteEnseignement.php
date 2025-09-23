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
}
