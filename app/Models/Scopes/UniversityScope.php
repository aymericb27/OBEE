<?php

// app/Models/Scopes/UniversityScope.php
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
        if (!$user) return;

        $builder->where($model->getTable().'.university_id', $user->university_id);
    }
}
