<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UniversityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // ✅ Admin : accès à toutes les données
        if ($user->role === 'admin') {
            return;
        }

        // ✅ Sécurité : si pas d'université => aucune donnée
        if (!$user->university_id) {
            $builder->whereRaw('1 = 0');
            return;
        }

        // ✅ User normal : filtrage par université
        $builder->where($model->getTable() . '.university_id', $user->university_id);
    }
}
