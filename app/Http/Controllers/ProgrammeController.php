<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise;
use App\Models\Pro_semester;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use App\Services\CodeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgrammeController extends Controller
{
    public function __construct(private CodeGeneratorService $codeGen) {}

    public function get(Request $request = null)
    {
        $programmes = Programme::select('code', 'id', 'ects', 'name')->get();
        return $programmes;
    }


    public function addUES(Request $request)
    {
        $validated = $request->validate([
            'programme_id' => 'required|integer|exists:programme,id',
            'semester' => 'required|integer|min:1',
            'list' => 'required|array|min:1',
            'list.*.id' => 'required|integer|exists:unite_enseignement,id',
        ]);

        $universityId = Auth::user()->university_id;

        // 🔹 récupérer le pro_semester correspondant
        $proSemester = DB::table('pro_semester')
            ->where('fk_programme', $validated['programme_id'])
            ->where('semester', $validated['semester'])
            ->where('university_id', $universityId)
            ->first();

        if (!$proSemester) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable pour ce programme',
            ], 404);
        }

        $rows = [];

        $programme = Programme::findOrFail($validated['programme_id']);

        foreach ($validated['list'] as $ue) {
            $programme->ues()->syncWithoutDetaching([
                $ue['id'] => [
                    'fk_semester' => $proSemester->id,
                    'university_id' => $universityId,
                ],
            ]);
        }


        try {
            // ✅ insertOrIgnore pour respecter l’unicité
            DB::table('ue_programme')->insertOrIgnore($rows);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’ajout des UE',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'UE ajoutées au programme avec succès',
            'added' => count($rows),
        ]);
    }

    public function getPrerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ues = AcquisApprentissageVise::select('acquis_apprentissage_vise.id','code','name')->join('aavpro_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')->where('fk_programme', $validated['id'])->get();
        return $ues;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|min:0',
            'semestre' => 'required|integer|between:2,10',
            'semestresCredits' => 'required|array',
            'semestresCredits.*' => 'required|integer|min:0',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'code' => 'nullable|string|max:50',
        ]);

        // ✅ si vide => générer
        if (empty($validated['code'])) {
            $validated['code'] = $this->codeGen->nextProgramme(); // (si tu as un nextProgramme(), mets-le ici)
        }

        $validated['university_id'] = Auth::user()->university_id;

        return DB::transaction(function () use ($validated) {
            // ----- Création du programme -----
            $programme = Programme::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'ects' => $validated['ects'],
                'university_id' => $validated['university_id'],
            ]);

            // ----- Création des semestres (pro_semester) -----
            $rows = [];
            for ($i = 1; $i <= (int) $validated['semestre']; $i++) {
                $rows[] = [
                    'ects' => (int) ($validated['semestresCredits'][$i] ?? 0),
                    'semester' => $i,
                    'fk_programme' => $programme->id,
                    'university_id' => $validated['university_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('pro_semester')->insert($rows);

            if (isset($validated['aavprerequis'])) {
                $pivotData = [];
                foreach ($validated['aavprerequis'] as $item) {
                    $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
                }
                $programme->prerequis()->sync($pivotData);
            }
            return response()->json([
                'success' => true,
                'message' => 'Le programme a été créé correctement',
                'id' => $programme->id,
            ]);
        });
    }


    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
        ]);
        $pro = Programme::findOrFail($validated['id']);
        $pro->delete();
        return response()->json([
            'success' => true,
            'message' => "Programme supprimé avec succès.",
        ]);
    }
/* 
    public function addSemestre(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
        ]);

        // Récupération du programme
        $programme = Programme::findOrFail($validated['id']);

        // Incrémentation du nombre de semestres
        $programme->semestre = $programme->semestre + 1;
        $programme->save();

        // Retourne le programme mis à jour avec la nouvelle structure de semestres

        return response()->json([
            'success' => true,
            'message' => 'Le semestre a été crée correctement',
            'id' => $programme->id
        ]);
    } */
    /**
     * Update an existing programme
     */

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|min:0',

            // le front envoie ça
            'semestre' => 'required|integer|between:2,10',
            'semestresCredits' => 'required|array',
            'semestresCredits.*' => 'required|integer|min:0',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
        ]);

        $programme = Programme::findOrFail($validated['id']);
        $universityId = Auth::user()->university_id;

        $existingCode = Programme::where('university_id', $universityId)
            ->where('code', $validated['code'])
            ->where('id', '!=', $programme->id)
            ->exists();

        if ($existingCode) {
            return response()->json([
                'success' => false,
                'message' => 'Le sigle existe deja pour cette universite.',
            ], 422);
        }

        return DB::transaction(function () use ($validated, $programme, $universityId) {

            // ✅ update programme (sans semestre)
            $programme->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'ects' => $validated['ects'],
            ]);

            // ✅ conserver les semestres existants pour préserver les liens UE
            $existingSemesters = DB::table('pro_semester')
                ->where('fk_programme', $programme->id)
                ->where('university_id', $universityId)
                ->get()
                ->keyBy('semester');

            $keptSemesterIds = [];
            for ($i = 1; $i <= (int) $validated['semestre']; $i++) {
                $ects = (int) ($validated['semestresCredits'][$i] ?? 0);

                if (isset($existingSemesters[$i])) {
                    $semesterRow = $existingSemesters[$i];
                    DB::table('pro_semester')
                        ->where('id', $semesterRow->id)
                        ->update([
                            'ects' => $ects,
                            'updated_at' => now(),
                        ]);
                    $keptSemesterIds[] = $semesterRow->id;
                    continue;
                }

                $keptSemesterIds[] = DB::table('pro_semester')->insertGetId([
                    'ects' => $ects,
                    'semester' => $i,
                    'fk_programme' => $programme->id,
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ✅ supprimer uniquement les semestres retirés de la maquette
            DB::table('pro_semester')
                ->where('fk_programme', $programme->id)
                ->where('university_id', $universityId)
                ->whereNotIn('id', $keptSemesterIds)
                ->delete();

            if (isset($validated['aavprerequis'])) {
                $pivotData = [];
                foreach ($validated['aavprerequis'] as $item) {
                    $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
                }
                $programme->prerequis()->sync($pivotData);
            }
            return response()->json([
                'success' => true,
                'message' => 'Programme modifié avec succès',
                'id' => $programme->id,
            ]);
        });
    }

    public function copy(Request $request)
    {
        $validated = $request->validate([
            'source_id' => 'required|integer|exists:programme,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
        ]);

        $universityId = Auth::user()->university_id;

        return DB::transaction(function () use ($validated, $universityId) {
            $sourceProgramme = Programme::where('id', $validated['source_id'])
                ->where('university_id', $universityId)
                ->firstOrFail();

            $existingCode = Programme::where('university_id', $universityId)
                ->where('code', $validated['code'])
                ->exists();

            if ($existingCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le sigle existe deja pour cette universite.',
                ], 422);
            }

            $newProgramme = Programme::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'ects' => $sourceProgramme->ects,
                'university_id' => $universityId,
            ]);

            $sourceSemesters = DB::table('pro_semester')
                ->where('fk_programme', $sourceProgramme->id)
                ->where('university_id', $universityId)
                ->orderBy('semester')
                ->get();

            $semesterIdMap = [];
            foreach ($sourceSemesters as $semester) {
                $newSemesterId = DB::table('pro_semester')->insertGetId([
                    'ects' => $semester->ects,
                    'semester' => $semester->semester,
                    'fk_programme' => $newProgramme->id,
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $semesterIdMap[$semester->id] = $newSemesterId;
            }

            $sourcePrerequis = DB::table('aavpro_prerequis')
                ->where('fk_programme', $sourceProgramme->id)
                ->where('university_id', $universityId)
                ->get();

            if ($sourcePrerequis->isNotEmpty()) {
                $prerequisRows = [];
                foreach ($sourcePrerequis as $prerequis) {
                    $prerequisRows[] = [
                        'fk_acquis_apprentissage_prerequis' => $prerequis->fk_acquis_apprentissage_prerequis,
                        'fk_programme' => $newProgramme->id,
                        'university_id' => $universityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('aavpro_prerequis')->insert($prerequisRows);
            }

            $sourceUes = DB::table('ue_programme')
                ->where('fk_programme', $sourceProgramme->id)
                ->where('university_id', $universityId)
                ->get();

            if ($sourceUes->isNotEmpty()) {
                $ueRows = [];
                foreach ($sourceUes as $ueLink) {
                    if (!isset($semesterIdMap[$ueLink->fk_semester])) {
                        continue;
                    }
                    $ueRows[] = [
                        'fk_unite_enseignement' => $ueLink->fk_unite_enseignement,
                        'fk_programme' => $newProgramme->id,
                        'fk_semester' => $semesterIdMap[$ueLink->fk_semester],
                        'university_id' => $universityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($ueRows)) {
                    DB::table('ue_programme')->insert($ueRows);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Programme copié avec succes',
                'id' => $newProgramme->id,
            ]);
        });
    }


    public function getUE(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ues = UniteEnseignement::join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')->where('fk_programme', $validated['id'])->get();
        return $ues;
    }

    public function getTree(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $programme = Programme::select('id', 'code', 'name', 'ects')
            ->where('id', $validated['id'])
            ->firstOrFail();

        $semesters = DB::table('pro_semester')
            ->where('fk_programme', $programme->id)
            ->orderBy('semester')
            ->get();

        $listSemestre = [];

        foreach ($semesters as $sem) {
            $ues = $this->getUEBySemester($programme->id, $sem->id);

            $listSemestre[] = [
                'id' => $sem->id,
                'number' => $sem->semester,
                'ects' => $sem->ects,
                'UES' => $ues,
                'countECTS' => $ues->sum('ects'),
            ];
        }

        // ✅ retourne un array (pas mutation d'attribut dynamique)
        return response()->json([
            'id' => $programme->id,
            'code' => $programme->code,
            'name' => $programme->name,
            'ects' => $programme->ects,
            'listSemestre' => $listSemestre,
        ]);
    }

    public function getUEBySemester($programmeId, $proSemesterId)
    {
        return UniteEnseignement::select(
            'unite_enseignement.id',
            'unite_enseignement.code',
            'unite_enseignement.name',
            'unite_enseignement.ects'
        )
            ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('ue_programme.fk_programme', $programmeId)
            ->where('ue_programme.fk_semester', $proSemesterId) // ✅ CORRECTION
            ->whereNotIn('unite_enseignement.id', function ($query) {
                $query->select('fk_ue_child')->from('element_constitutif');
            })
            ->with('children')
            ->get();
    }


    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('code', 'id', 'name', 'ects')
            ->where('id', $validated['id'])
            ->first();
        $response->semester = Pro_semester::select('ects', 'semester')
            ->where(
                'fk_programme',
                $validated['id']
            )->get();
        return $response;
    }
}
