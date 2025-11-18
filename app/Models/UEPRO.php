<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UEPRO extends Model
{
    protected $table = "ue_programme";
    protected $fillable = ["fk_unite_enseignement", "fk_programme", 'semester'];
}
