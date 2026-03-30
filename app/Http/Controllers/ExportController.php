<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{

    public function exportUE($ueId)
    {
        $export = new \App\Exports\UEExport($ueId);
        return $export->download();
    }

    public function exportAAT($aatID)
    {
        $export = new \App\Exports\AATExport($aatID);
        return $export->download();
    }

    public function exportAAV($aavID)
    {
        $export = new \App\Exports\AAVExport($aavID);
        return $export->download();
    }

    public function exportPRO($proID)
    {
        $export = new \App\Exports\PROExport($proID);
        return $export->download();
    }

    public function exportProgramAnalysisUesWithErrors(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'anomaly_code' => 'nullable|string|max:50',
        ]);

        $export = new \App\Exports\ProgramAnalysisAnomalyUEExport(
            (int) $validated['id'],
            (int) Auth::user()->university_id,
            $validated['anomaly_code'] ?? null
        );

        return $export->download();
    }

    public function exportProgramAnalysisContributionMatrix(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'aats' => 'required|array',
            'aats.*.id' => 'required|integer',
            'aats.*.code' => 'nullable|string|max:500',
            'aats.*.name' => 'nullable|string|max:500',
            'rows' => 'required|array',
            'rows.*.ue_id' => 'nullable|integer',
            'rows.*.ue_code' => 'nullable|string|max:500',
            'rows.*.ue_name' => 'nullable|string|max:500',
            'rows.*.aav_id' => 'nullable|integer',
            'rows.*.aav_code' => 'nullable|string|max:500',
            'rows.*.aav_name' => 'nullable|string|max:500',
            'rows.*.contributions' => 'nullable|array',
        ]);

        $programme = Programme::select('code', 'name')
            ->where('id', (int) $validated['id'])
            ->where('university_id', (int) Auth::user()->university_id)
            ->firstOrFail();

        $export = new \App\Exports\ProgramAnalysisContributionMatrixExport(
            (string) ($programme->code ?? ''),
            (string) ($programme->name ?? ''),
            $validated['aats'] ?? [],
            $validated['rows'] ?? []
        );

        return $export->download();
    }

}
