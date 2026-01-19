<?php
// app/Models/Concerns/BelongsToUniversity.php
namespace App\Models\Concerns;

use App\Models\Scopes\UniversityScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToUniversity
{
    protected static function bootBelongsToUniversity(): void
    {
        static::addGlobalScope(new UniversityScope);

        static::creating(function ($model) {
            if (!Auth::user()) return;
            $model->university_id = Auth::user()->university_id;
        });

        static::updating(function ($model) {
            if ($model->isDirty('university_id')) {
                throw new \RuntimeException('university_id cannot be changed.');
            }
        });
    }
}
