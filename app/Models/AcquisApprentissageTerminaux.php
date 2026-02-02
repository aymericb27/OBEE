<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUniversity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcquisApprentissageTerminaux extends Model
{

    use BelongsToUniversity;

    use HasFactory;
    protected $table = "acquis_apprentissage_terminaux";
    protected $fillable = [
        'description',
        'name',
        'code',
        'university_id',
        'level_contribution',

    ];

    public function aav()
    {
        return $this->belongsToMany(
            AcquisApprentissageVise::class,
            'aav_aat',
            'fk_aat', // clé pivot vers AAT (ce modèle)
            'fk_aav'  // clé pivot vers AAV (related)
        )->withPivot('contribution')
            ->withTimestamps();
    }
}
