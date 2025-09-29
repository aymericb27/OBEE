<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcquisApprentissageVise extends Model
{
    use HasFactory;
    protected $table = "acquis_apprentissage_vise";
    protected $fillable = [
        'description',
        'name',
        'code',
        'fk_AAT',
    ];
}
