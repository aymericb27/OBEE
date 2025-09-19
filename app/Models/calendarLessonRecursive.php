<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class calendarLessonRecursive extends Model
{
    use HasFactory;

    protected $table = 'calendar_lesson_recursive';

    use HasFactory;
        protected $fillable = [
        'date_lesson_begin',
        'date_lesson_end',
        'day_week',
        'time_begin',
        'time_end',
        'fk_element_constitutif',
    ];

    public function ue()
    {
        return $this->belongsTo(UniteEnseignement::class, 'fk_element_constitutif');
    }
}
