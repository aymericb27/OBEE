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
}
