<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AAVUE extends Model
{
    use HasFactory;
    protected $table = "aavue";
    protected $fillable = ["fk_acquis_apprentissage_vise","fk_unite_enseignement"];
}
