<?php

namespace App\Http\Controllers;

use App\Models\CalendarLesson as ModelCalendarLesson;
use App\Models\calendarLessonRecursive as ModelCalendarLessonRecursive;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarLesson extends Controller
{
    public function index()
    {
        $events = ModelCalendarLesson::select('calendar_lesson.id as idcal','element_constitutif.id as idec', 'date_lesson', 'time_begin', 'time_end', 'element_constitutif.name as name')
        ->join('element_constitutif', 'calendar_lesson.fk_element_constitutif', '=', 'element_constitutif.id')->get();

        /*         $eventsRecursive = ModelCalendarLessonRecursive::select("date_lesson_begin,date_lesson_end,day_week,time_begin,time_end")
         ->get(); */
        $formatted = $events->map(function ($event) {
            return [
                'start' => $event->date_lesson . ' ' . $event->time_begin,
                'end' => $event->date_lesson . ' ' . $event->time_end,
                'title' => $event->name,
                'idcal' => $event->idcal,
                'idec' => $event->idec,
            ];
        });

        return response()->json($formatted);
    }

    public function addToCalendar($date, $event) {}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'selected_ec' => 'required|exists:element_constitutif,id',
            'time_begin' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_begin',
            'is_recurring' => 'nullable|boolean',

            // soit un event normal
            'date_lesson' => 'nullable|date',

            // soit un event récurrent
            'day_week_recurring' => 'nullable|integer|max:7',
            'is_recurring' => 'required|boolean',
            'date_lesson_begin_recurring' => 'nullable|date',
            'date_lesson_end_recurring' => 'nullable|date|after:date_lesson_begin_recurring',
        ]);

        if (!$validated['is_recurring']) {
            $event = ModelCalendarLesson::create([
                'fk_element_constitutif' => $validated['selected_ec'],
                'date_lesson' => $validated['date_lesson'],
                'time_begin' => $validated['time_begin'],
                'time_end' => $validated['time_end'],
            ]);
        } else {
            $dayOfWeek = (int) $validated['day_week_recurring']; // ex: 1=lundi

            $start = Carbon::parse($validated['date_lesson_begin_recurring']);
            $end   = Carbon::parse($validated['date_lesson_end_recurring']);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if ($date->dayOfWeekIso === $dayOfWeek) {
                    $event = ModelCalendarLesson::create([
                        'fk_element_constitutif' => $validated['selected_ec'],
                        'date_lesson' => $date->toDateString(),
                        'time_begin' => $validated['time_begin'],
                        'time_end' => $validated['time_end'],
                    ]);
                }
            }
        }
        return response()->json(['message' => 'Événement enregistré avec succès', 'event' => $event]);
    }
}
