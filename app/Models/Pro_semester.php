<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pro_semester extends Model
{
    protected $table = "pro_semester";
    protected $fillable = [
        'fk_programme',
        'semester',
        'ects',
        'university_id',
    ];}
