<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $fillable = ['name', 'code'];
    protected $table = 'universities';

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }
}
