<?php

namespace App\Http\Controllers;

use App\Exports\ProgExport;
use App\Models\Programme;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProgrammeController extends Controller
{
    public function get(Request $request = null)
    {
        $programmes = Programme::select('code', 'id', 'ects', 'name')->get();
        return $programmes;
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('code', 'id', 'name', 'ects')
            ->where('id', $validated['id'])
            ->first();
        return $response;
    }
}
