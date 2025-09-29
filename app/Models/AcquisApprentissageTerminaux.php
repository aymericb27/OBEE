<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcquisApprentissageTerminaux extends Model
{
    use HasFactory;
    protected $table = "acquis_apprentissage_terminaux";
    protected $fillable = [
        'description',
        'name',
        'code',
    ];
}
