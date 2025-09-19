<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CalendarLesson extends Model
{
    protected $table = 'calendar_lesson';

    use HasFactory;
        protected $fillable = [
        'date_lesson',
        'time_begin',
        'time_end',
        'fk_element_constitutif',
    ];

    public function ue()
    {
        return $this->belongsTo(UniteEnseignement::class, 'fk_element_constitutif');
    }
}
