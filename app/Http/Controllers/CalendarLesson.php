<?php

namespace App\Http\Controllers;

use App\Models\CalendarLesson as ModelCalendarLesson;
use App\Models\calendarLessonRecursive as ModelCalendarLessonRecursive;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarLesson extends Controller
{
 public function store(Request $request)
    {
        $validated = $request->validate([
            'selected_ec'   => 'required|exists:element_constitutif,id',
            'time_begin'  => 'required|date_format:H:i',
            'time_end'    => 'required|date_format:H:i|after:time_begin',
            'is_recurring' => 'nullable|boolean',
    
            // soit un event normal
            'date_lesson' => 'nullable|date',

            // soit un event récurrent
            'day_week_recurring' =>"nullable|integer|max:7",
            'is_recurring'                => 'required|boolean',
            'date_lesson_begin_recurring' => 'nullable|date',
            'date_lesson_end_recurring'   => 'nullable|date|after:date_lesson_begin_recurring',
    
        ]);

        if(!$validated['is_recurring']){
            $event = ModelCalendarLesson::create([
                'fk_element_constitutif'  => $validated['selected_ec'],
                'date_lesson'  => $validated['date_lesson'],
                'time_begin'  => $validated['time_begin'],
                'time_end'    => $validated['time_end'],
            ]);
        } else{
            $event = ModelCalendarLessonRecursive::create([
                'fk_element_constitutif'  => $validated['selected_ec'],
                'date_lesson_begin'  => $validated['date_lesson_begin_recurring'],
                'date_lesson_end'  => $validated['date_lesson_end_recurring'],
                'day_week'  => $validated['day_week_recurring'],
                'time_begin'  => $validated['time_begin'],
                'time_end'    => $validated['time_end'],
            ]);
        }

        return response()->json(['message' => 'Événement enregistré avec succès', 'event' => $event]);
    }
}
