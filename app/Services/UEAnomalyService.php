<?php

namespace App\Services;

use App\Models\UniteEnseignement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UEAnomalyService
{
    public const CODE_HAS_ANOMALY = 'UE_ANOM_01';
    public const CODE_PREREQ_AS_UE = 'UE_ANOM_02';
    public const CODE_PREREQ_OUTSIDE_ALLOWED = 'UE_ANOM_03';
    public const CODE_EMPTY_AAV_LIST = 'UE_ANOM_04';
    public const CODE_EMPTY_CREDITS = 'UE_ANOM_05';
    public const CODE_MISSING_SEMESTER = 'UE_ANOM_06';
    public const CODE_MISSING_AAV_AAT_CONTRIBUTION = 'UE_ANOM_07';
    public const CODE_MISSING_AAT_LEVEL = 'UE_ANOM_08';
    public const CODE_INCOHERENT_AAT_CONTRIBUTION = 'UE_ANOM_09';
    public const CODE_PREREQ_AAV_NOT_IN_PREREQ_UE = 'UE_ANOM_10';
    public const CODE_NOT_IN_ANY_PROGRAM = 'UE_ANOM_11';
    public const CODE_PREREQ_UE_OUTSIDE_ALLOWED = 'UE_ANOM_12';

    /**
     * Recompute and persist anomalies for one UE.
     */
    public function recomputeForUE(int $ueId): array
    {
        $ue = UniteEnseignement::withoutGlobalScopes()
            ->select('id', 'code', 'name', 'ects', 'university_id')
            ->findOrFail($ueId);

        $anomalies = $this->buildForUE((int) $ue->id, (int) $ue->university_id, $ue->ects);
        $this->persistForUE((int) $ue->id, (int) $ue->university_id, $anomalies);

        return [
            'ue_id' => (int) $ue->id,
            'count' => count($anomalies),
            'codes' => collect($anomalies)->pluck('code')->unique()->values()->all(),
        ];
    }

    /**
     * Recompute anomalies for every UE of one university.
     */
    public function recomputeForUniversity(int $universityId): array
    {
        $ueIds = UniteEnseignement::withoutGlobalScopes()
            ->where('university_id', $universityId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $total = 0;
        foreach ($ueIds as $ueId) {
            $result = $this->recomputeForUE($ueId);
            $total += (int) ($result['count'] ?? 0);
        }

        $total += $this->recomputeEmptySemesterAnomalies($universityId);

        return [
            'university_id' => $universityId,
            'ues' => count($ueIds),
            'anomalies' => $total,
        ];
    }

    public function recomputeForUEIds(array $ueIds): array
    {
        $ids = collect($ueIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $total = 0;
        foreach ($ids as $ueId) {
            $result = $this->recomputeForUE($ueId);
            $total += (int) ($result['count'] ?? 0);
        }

        return [
            'ues' => count($ids),
            'anomalies' => $total,
        ];
    }

    /**
     * Recompute anomalies about empty semesters (UE_ANOM_06 with ue_id = null)
     * for the full university scope.
     */
    public function recomputeEmptySemesterAnomaliesForUniversity(int $universityId): int
    {
        return $this->recomputeEmptySemesterAnomalies($universityId);
    }

    public function recomputeForProgram(int $programId, int $universityId, array $extraUeIds = []): array
    {
        $programUeIds = DB::table('ue_programme')
            ->where('university_id', $universityId)
            ->where('fk_programme', $programId)
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->all();

        $ueIds = array_values(array_unique(array_merge(
            $programUeIds,
            collect($extraUeIds)->map(fn($id) => (int) $id)->all()
        )));

        return $this->recomputeForUEIds($ueIds);
    }

    public function recomputeForAAV(int $aavId, int $universityId): array
    {
        $viseUeIds = DB::table('aavue_vise')
            ->where('university_id', $universityId)
            ->where('fk_acquis_apprentissage_vise', $aavId)
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->all();

        $prereqUeIds = DB::table('aavue_prerequis')
            ->where('university_id', $universityId)
            ->where('fk_acquis_apprentissage_prerequis', $aavId)
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->all();

        return $this->recomputeForUEIds(array_values(array_unique(array_merge($viseUeIds, $prereqUeIds))));
    }

    public function recomputeForAAT(int $aatId, int $universityId): array
    {
        $directUeIds = DB::table('ue_aat')
            ->where('university_id', $universityId)
            ->where('fk_aat', $aatId)
            ->pluck('fk_ue')
            ->map(fn($id) => (int) $id)
            ->all();

        $viaAavUeIds = DB::table('aav_aat as aa')
            ->join('aavue_vise as av', function ($join) use ($universityId) {
                $join->on('av.fk_acquis_apprentissage_vise', '=', 'aa.fk_aav')
                    ->where('av.university_id', '=', $universityId);
            })
            ->where('aa.university_id', $universityId)
            ->where('aa.fk_aat', $aatId)
            ->pluck('av.fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->all();

        return $this->recomputeForUEIds(array_values(array_unique(array_merge($directUeIds, $viaAavUeIds))));
    }

    /**
     * Summary used by list/tree UI.
     */
    public function getSummaryForUEIds(array $ueIds, int $universityId): Collection
    {
        $ids = collect($ueIds)->map(fn($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = DB::table('anomalies')
            ->select('ue_id', 'severity')
            ->where('university_id', $universityId)
            ->whereIn('ue_id', $ids->all())
            ->where('is_resolved', false)
            ->where('code', '!=', self::CODE_HAS_ANOMALY)
            ->get()
            ->groupBy('ue_id');

        $map = [];
        foreach ($rows as $ueId => $group) {
            $severity = 'info';
            foreach ($group as $row) {
                $severity = $this->maxSeverity($severity, (string) ($row->severity ?? 'info'));
            }
            $map[(int) $ueId] = [
                'count' => $group->count(),
                'severity' => $severity,
                'has_anomaly' => $group->count() > 0,
            ];
        }

        return collect($map);
    }

    public function getDetailsForUE(int $ueId, int $universityId): array
    {
        $programIds = DB::table('ue_programme')
            ->where('university_id', $universityId)
            ->where('fk_unite_enseignement', $ueId)
            ->pluck('fk_programme')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $rows = DB::table('anomalies')
            ->select('id', 'code', 'severity', 'message', 'details', 'program_id', 'semester_id', 'detected_at')
            ->where('university_id', $universityId)
            ->where('is_resolved', false)
            ->where('code', '!=', self::CODE_HAS_ANOMALY)
            ->where(function ($query) use ($ueId, $programIds) {
                $query->where('ue_id', $ueId);

                if (!empty($programIds)) {
                    $query->orWhere(function ($nested) use ($programIds) {
                        $nested->whereNull('ue_id')
                            ->where('code', self::CODE_MISSING_SEMESTER)
                            ->whereIn('program_id', $programIds);
                    });
                }
            })
            ->orderByRaw("
                CASE severity
                    WHEN 'error' THEN 3
                    WHEN 'warning' THEN 2
                    ELSE 1
                END DESC
            ")
            ->orderBy('code')
            ->get()
            ->map(function ($row) {
                $details = $row->details;
                if (is_string($details) && $details !== '') {
                    $decoded = json_decode($details, true);
                    $details = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                }
                return [
                    'id' => (int) $row->id,
                    'code' => (string) $row->code,
                    'severity' => (string) $row->severity,
                    'message' => (string) $row->message,
                    'details' => is_array($details) ? $details : [],
                    'program_id' => $row->program_id !== null ? (int) $row->program_id : null,
                    'semester_id' => $row->semester_id !== null ? (int) $row->semester_id : null,
                    'detected_at' => $row->detected_at,
                ];
            })
            ->values();

        $missingSemesterRows = $rows->where('code', self::CODE_MISSING_SEMESTER)->values();
        if ($missingSemesterRows->count() > 1) {
            $severity = 'info';
            foreach ($missingSemesterRows as $row) {
                $severity = $this->maxSeverity($severity, (string) ($row['severity'] ?? 'info'));
            }

            $impactedSemesters = $missingSemesterRows
                ->flatMap(function ($row) {
                    $details = is_array($row['details'] ?? null) ? $row['details'] : [];
                    if (!empty($details['impacted_semesters']) && is_array($details['impacted_semesters'])) {
                        return $details['impacted_semesters'];
                    }
                    if (
                        array_key_exists('semester_number', $details) ||
                        array_key_exists('semester_id', $details) ||
                        array_key_exists('program_id', $details)
                    ) {
                        return [[
                            'program_id' => $details['program_id'] ?? $row['program_id'] ?? null,
                            'program_code' => $details['program_code'] ?? null,
                            'program_name' => $details['program_name'] ?? null,
                            'semester_id' => $details['semester_id'] ?? $row['semester_id'] ?? null,
                            'semester_number' => $details['semester_number'] ?? null,
                        ]];
                    }
                    return [[
                        'program_id' => $row['program_id'] ?? null,
                        'semester_id' => $row['semester_id'] ?? null,
                    ]];
                })
                ->filter(fn($item) => is_array($item))
                ->values()
                ->all();

            $labels = collect($impactedSemesters)
                ->map(function ($item) {
                    $programLabel = trim(implode(' - ', array_filter([
                        $item['program_code'] ?? null,
                        $item['program_name'] ?? null,
                    ])));
                    $semesterLabel = isset($item['semester_number']) && $item['semester_number'] !== null
                        ? 'S' . (int) $item['semester_number']
                        : 'semestre non defini';
                    if ($programLabel !== '') {
                        return "{$programLabel} ({$semesterLabel})";
                    }
                    return $semesterLabel;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $rows = $rows->reject(fn($row) => ($row['code'] ?? null) === self::CODE_MISSING_SEMESTER)->values();
            $rows->push([
                'id' => 0,
                'code' => self::CODE_MISSING_SEMESTER,
                'severity' => $severity,
                'message' => 'Le semestre de cette UE est manquant. Semestres impactes: ' . implode(', ', $labels) . '.',
                'details' => [
                    'impacted_semesters' => $impactedSemesters,
                ],
                'program_id' => null,
                'semester_id' => null,
                'detected_at' => null,
            ]);
        }

        return $rows->values()->all();
    }

    /**
     * Summary used by semester UI (program tree).
     */
    public function getSummaryForSemesterIds(array $semesterIds, int $universityId): Collection
    {
        $ids = collect($semesterIds)->map(fn($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = DB::table('anomalies')
            ->select('semester_id', 'severity')
            ->where('university_id', $universityId)
            ->whereIn('semester_id', $ids->all())
            ->where('is_resolved', false)
            ->whereNull('ue_id')
            ->get()
            ->groupBy('semester_id');

        $map = [];
        foreach ($rows as $semesterId => $group) {
            $severity = 'info';
            foreach ($group as $row) {
                $severity = $this->maxSeverity($severity, (string) ($row->severity ?? 'info'));
            }
            $map[(int) $semesterId] = [
                'count' => $group->count(),
                'severity' => $severity,
                'has_anomaly' => $group->count() > 0,
            ];
        }

        return collect($map);
    }

    private function buildForUE(int $ueId, int $universityId, $ects): array
    {
        $collector = [];

        $contexts = $this->loadProgrammeContexts($ueId, $universityId);
        $ueAavIds = $this->loadUEAavIds($ueId, $universityId);
        $uePrereqIds = $this->loadUEPrereqIds($ueId, $universityId);
        $ueAatIds = $this->loadUEAatIds($ueId, $universityId);
        $uePrereqUERows = $this->loadUEPrereqUERows($ueId, $universityId);

        $contextProgramIds = collect($contexts)
            ->pluck('program_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $programIdsForSemesterRule = $contextProgramIds;
        $programMetaForSemesterRule = DB::table('programme')
            ->select('id', 'code', 'name')
            ->where('university_id', $universityId)
            ->whereIn('id', empty($programIdsForSemesterRule) ? [-1] : $programIdsForSemesterRule)
            ->get()
            ->keyBy('id');

        $prereqRows = $this->loadAavRows($uePrereqIds, $universityId)->keyBy('id');
        $aavRows = $this->loadAavRows($ueAavIds, $universityId)->keyBy('id');

        if (empty($contexts)) {
            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_NOT_IN_ANY_PROGRAM,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Cette UE ne figure dans aucun programme.'
                )
            );
        }

        if (empty($ueAavIds)) {
            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_EMPTY_AAV_LIST,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'La liste des AAV de cette UE est vide.'
                )
            );
        }

        if ($ects === null) {
            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_EMPTY_CREDITS,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Le nombre de credits de cette UE est vide.'
                )
            );
        }

        $contextSemesterIds = collect($contexts)
            ->pluck('semester_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $semesterNumberById = DB::table('pro_semester')
            ->where('university_id', $universityId)
            ->whereIn('id', empty($contextSemesterIds) ? [-1] : $contextSemesterIds)
            ->pluck('semester', 'id')
            ->mapWithKeys(fn($semester, $id) => [(int) $id => $semester !== null ? (int) $semester : null])
            ->all();

        $missingSemesterContexts = [];
        foreach ($contexts as $context) {
            if (empty($context['semester_id']) || $context['semester_number'] === null) {
                $programId = (int) ($context['program_id'] ?? 0);
                $programCode = $programMetaForSemesterRule->get($programId)->code ?? null;
                $programName = $programMetaForSemesterRule->get($programId)->name ?? null;
                $semesterId = $context['semester_id'] !== null ? (int) $context['semester_id'] : null;
                $semesterNumber = $context['semester_number'] !== null
                    ? (int) $context['semester_number']
                    : (($semesterId !== null && array_key_exists($semesterId, $semesterNumberById))
                        ? $semesterNumberById[$semesterId]
                        : null);
                $missingSemesterContexts[] = [
                    'program_id' => $programId > 0 ? $programId : null,
                    'program_code' => $programCode,
                    'program_name' => $programName,
                    'semester_id' => $semesterId,
                    'semester_number' => $semesterNumber,
                ];
            }
        }

        if (!empty($missingSemesterContexts)) {
            $impactedSemesters = collect($missingSemesterContexts)
                ->map(function ($row) {
                    $programLabel = trim(implode(' - ', array_filter([
                        $row['program_code'] ?? null,
                        $row['program_name'] ?? null,
                    ])));
                    $semesterLabel = $row['semester_number'] !== null
                        ? 'S' . (int) $row['semester_number']
                        : 'semestre non defini';

                    if ($programLabel !== '') {
                        return "{$programLabel} ({$semesterLabel})";
                    }

                    return $semesterLabel;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_MISSING_SEMESTER,
                    $ueId,
                    null,
                    null,
                    'error',
                    'Le semestre de cette UE est manquant. Semestres impactes: ' . implode(', ', $impactedSemesters) . '.',
                    [
                        'impacted_semesters' => $missingSemesterContexts,
                    ]
                )
            );
        }

        $programPrereqCache = [];
        $previousAavCache = [];
        $previousUeCache = [];
        $prereqOutsideAllowedMap = [];
        $uePrereqOutsideAllowedMap = [];

        foreach ($contexts as $context) {
            $programId = (int) $context['program_id'];
            $semesterId = $context['semester_id'] ? (int) $context['semester_id'] : null;
            $semesterNumber = $context['semester_number'] !== null ? (int) $context['semester_number'] : null;

            if (!isset($programPrereqCache[$programId])) {
                $programPrereqCache[$programId] = $this->loadProgramPrereqIds($programId, $universityId);
            }

            $previousKey = $programId . '|' . ($semesterNumber ?? 'null');
            if (!isset($previousAavCache[$previousKey])) {
                $previousAavCache[$previousKey] = $this->loadPreviousSemesterAavIds(
                    $programId,
                    $semesterNumber,
                    $universityId
                );
            }
            if (!isset($previousUeCache[$previousKey])) {
                $previousUeCache[$previousKey] = $this->loadPreviousSemesterUEIds(
                    $programId,
                    $semesterNumber,
                    $universityId
                );
            }

            $allowed = array_flip(array_unique(array_merge(
                $programPrereqCache[$programId],
                $previousAavCache[$previousKey]
            )));

            foreach ($uePrereqIds as $prereqId) {
                if (!isset($allowed[$prereqId])) {
                    $prereq = $prereqRows->get($prereqId);
                    if (!isset($prereqOutsideAllowedMap[$prereqId])) {
                        $prereqOutsideAllowedMap[$prereqId] = [
                            'prereq_id' => (int) $prereqId,
                            'prereq_code' => $prereq->code ?? null,
                            'prereq_name' => $prereq->name ?? null,
                            'program_ids' => [],
                            'semester_ids' => [],
                        ];
                    }

                    if ($programId > 0) {
                        $prereqOutsideAllowedMap[$prereqId]['program_ids'][$programId] = true;
                    }
                    if ($semesterId !== null) {
                        $prereqOutsideAllowedMap[$prereqId]['semester_ids'][(int) $semesterId] = true;
                    }
                }
            }

            if ($semesterNumber === null || $uePrereqUERows->isEmpty()) {
                continue;
            }

            $allowedUeLookup = array_flip($previousUeCache[$previousKey]);
            foreach ($uePrereqUERows as $prereqUe) {
                $prereqUeId = (int) ($prereqUe->id ?? 0);
                if ($prereqUeId <= 0 || isset($allowedUeLookup[$prereqUeId])) {
                    continue;
                }

                if (!isset($uePrereqOutsideAllowedMap[$prereqUeId])) {
                    $uePrereqOutsideAllowedMap[$prereqUeId] = [
                        'prereq_ue_id' => $prereqUeId,
                        'prereq_ue_code' => $prereqUe->code ?? null,
                        'prereq_ue_name' => $prereqUe->name ?? null,
                        'program_ids' => [],
                        'semester_ids' => [],
                    ];
                }

                if ($programId > 0) {
                    $uePrereqOutsideAllowedMap[$prereqUeId]['program_ids'][$programId] = true;
                }
                if ($semesterId !== null) {
                    $uePrereqOutsideAllowedMap[$prereqUeId]['semester_ids'][$semesterId] = true;
                }
            }
        }

        if (!empty($prereqOutsideAllowedMap)) {
            $impactedPrereqs = collect($prereqOutsideAllowedMap)
                ->map(function ($row) {
                    $code = trim((string) ($row['prereq_code'] ?? ''));
                    $name = trim((string) ($row['prereq_name'] ?? ''));
                    if ($code !== '' && $name !== '') {
                        return "{$code} - {$name}";
                    }
                    if ($code !== '') {
                        return $code;
                    }
                    if ($name !== '') {
                        return $name;
                    }
                    return '#' . (int) ($row['prereq_id'] ?? 0);
                })
                ->filter()
                ->values()
                ->all();

            $programIds = collect($prereqOutsideAllowedMap)
                ->flatMap(fn($row) => array_keys($row['program_ids'] ?? []))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $semesterIds = collect($prereqOutsideAllowedMap)
                ->flatMap(fn($row) => array_keys($row['semester_ids'] ?? []))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_PREREQ_OUTSIDE_ALLOWED,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Un prerequis AAV ne fait pas partie des AAV des semestres precedents ni des prerequis du programme. Prerequis impactes: ' . implode(', ', $impactedPrereqs) . '.',
                    [
                        'impacted_prereqs' => array_values($prereqOutsideAllowedMap),
                        'impacted_program_ids' => $programIds,
                        'impacted_semester_ids' => $semesterIds,
                    ]
                )
            );
        }

        // Rule #2:
        // a prerequisite UE error exists when UE prerequisites are provided
        // but no AAV prerequisite is provided.
        if ($uePrereqUERows->isNotEmpty() && empty($uePrereqIds)) {
            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_PREREQ_AS_UE,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Des prerequis UE sont renseignes mais aucun prerequis AAV n est renseigne.',
                    [
                        'ue_prerequis' => $uePrereqUERows
                            ->map(fn($row) => [
                                'id' => (int) $row->id,
                                'code' => $row->code,
                                'name' => $row->name,
                            ])
                            ->values()
                            ->all(),
                        'aav_prerequis_count' => 0,
                    ]
                )
            );
        }

        if (!empty($uePrereqOutsideAllowedMap)) {
            $impactedPrereqUes = collect($uePrereqOutsideAllowedMap)
                ->map(function ($row) {
                    $code = trim((string) ($row['prereq_ue_code'] ?? ''));
                    $name = trim((string) ($row['prereq_ue_name'] ?? ''));
                    if ($code !== '' && $name !== '') {
                        return "{$code} - {$name}";
                    }
                    if ($code !== '') {
                        return $code;
                    }
                    if ($name !== '') {
                        return $name;
                    }
                    return '#' . (int) ($row['prereq_ue_id'] ?? 0);
                })
                ->filter()
                ->values()
                ->all();

            $programIds = collect($uePrereqOutsideAllowedMap)
                ->flatMap(fn($row) => array_keys($row['program_ids'] ?? []))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $semesterIds = collect($uePrereqOutsideAllowedMap)
                ->flatMap(fn($row) => array_keys($row['semester_ids'] ?? []))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_PREREQ_UE_OUTSIDE_ALLOWED,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Un prerequis UE ne fait pas partie des UE des semestres precedents. Prerequis impactes: ' . implode(', ', $impactedPrereqUes) . '.',
                    [
                        'impacted_ue_prereqs' => array_values($uePrereqOutsideAllowedMap),
                        'impacted_program_ids' => $programIds,
                        'impacted_semester_ids' => $semesterIds,
                    ]
                )
            );
        }

        // Rule #10:
        // if both prerequisite UE list and prerequisite AAV list are present:
        // 1) each prerequisite AAV must belong to at least one prerequisite UE (via AAV vises)
        // 2) each prerequisite UE must be represented by at least one prerequisite AAV
        if ($uePrereqUERows->isNotEmpty() && !empty($uePrereqIds)) {
            $uePrereqUeIds = $uePrereqUERows
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $normalizedPrereqAavIds = collect($uePrereqIds)
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $allowedAavIds = DB::table('aavue_vise')
                ->where('university_id', $universityId)
                ->whereIn('fk_unite_enseignement', empty($uePrereqUeIds) ? [-1] : $uePrereqUeIds)
                ->pluck('fk_acquis_apprentissage_vise')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $allowedAavLookup = array_flip($allowedAavIds);
            $invalidPrereqAavs = collect($uePrereqIds)
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0 && !isset($allowedAavLookup[$id]))
                ->map(function ($aavId) use ($prereqRows) {
                    $aav = $prereqRows->get($aavId);
                    return [
                        'aav_id' => (int) $aavId,
                        'aav_code' => $aav->code ?? null,
                        'aav_name' => $aav->name ?? null,
                    ];
                })
                ->values()
                ->all();

            $coveredUeIds = DB::table('aavue_vise')
                ->where('university_id', $universityId)
                ->whereIn('fk_unite_enseignement', empty($uePrereqUeIds) ? [-1] : $uePrereqUeIds)
                ->whereIn('fk_acquis_apprentissage_vise', empty($normalizedPrereqAavIds) ? [-1] : $normalizedPrereqAavIds)
                ->pluck('fk_unite_enseignement')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $coveredUeLookup = array_flip($coveredUeIds);
            $missingUePrerequis = $uePrereqUERows
                ->filter(fn($row) => !isset($coveredUeLookup[(int) $row->id]))
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                ])
                ->values()
                ->all();

            if (!empty($invalidPrereqAavs) || !empty($missingUePrerequis)) {
                $impactedAavLabels = collect($invalidPrereqAavs)
                    ->map(function ($row) {
                        $code = trim((string) ($row['aav_code'] ?? ''));
                        $name = trim((string) ($row['aav_name'] ?? ''));
                        if ($code !== '' && $name !== '') {
                            return "{$code} - {$name}";
                        }
                        if ($code !== '') {
                            return $code;
                        }
                        if ($name !== '') {
                            return $name;
                        }
                        return '#'.(int) ($row['aav_id'] ?? 0);
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $missingUeLabels = collect($missingUePrerequis)
                    ->map(function ($row) {
                        $code = trim((string) ($row['code'] ?? ''));
                        $name = trim((string) ($row['name'] ?? ''));
                        if ($code !== '' && $name !== '') {
                            return "{$code} - {$name}";
                        }
                        if ($code !== '') {
                            return $code;
                        }
                        if ($name !== '') {
                            return $name;
                        }
                        return '#' . (int) ($row['id'] ?? 0);
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $messageParts = [];
                if (!empty($impactedAavLabels)) {
                    $messageParts[] = 'AAV impactes: ' . implode(', ', $impactedAavLabels) . '.';
                }
                if (!empty($missingUeLabels)) {
                    $messageParts[] = 'UE prerequises sans AAV associe: ' . implode(', ', $missingUeLabels) . '.';
                }

                $this->pushAnomaly(
                    $collector,
                    $this->makeAnomaly(
                        self::CODE_PREREQ_AAV_NOT_IN_PREREQ_UE,
                        $ueId,
                        null,
                        null,
                        'warning',
                        'Incoherence entre prerequis UE et prerequis AAV. ' . implode(' ', $messageParts),
                        [
                            'ue_prerequis' => $uePrereqUERows
                                ->map(fn($row) => [
                                    'id' => (int) $row->id,
                                    'code' => $row->code,
                                    'name' => $row->name,
                                ])
                                ->values()
                                ->all(),
                            'invalid_aav_prerequis' => $invalidPrereqAavs,
                            'missing_ue_prerequis' => $missingUePrerequis,
                            'allowed_aav_ids' => $allowedAavIds,
                            'covered_ue_prerequis_ids' => $coveredUeIds,
                        ]
                    )
                );
            }
        }

        $programIds = collect($contexts)->pluck('program_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->all();
        $programMeta = DB::table('programme')
            ->select('id', 'code', 'name')
            ->where('university_id', $universityId)
            ->whereIn('id', empty($programIds) ? [-1] : $programIds)
            ->get()
            ->keyBy('id');
        $programAavAat = $this->loadProgramAavAatRows($programIds, $ueAavIds, $universityId);
        $aatIdsForDetails = collect($ueAatIds)
            ->merge(
                $programAavAat
                    ->pluck('fk_aat')
                    ->filter()
                    ->map(fn($id) => (int) $id)
            )
            ->unique()
            ->values()
            ->all();
        $aatRowsById = DB::table('acquis_apprentissage_terminaux')
            ->select('id', 'code', 'name')
            ->where('university_id', $universityId)
            ->whereIn('id', empty($aatIdsForDetails) ? [-1] : $aatIdsForDetails)
            ->get()
            ->keyBy('id');
        $aatLevels = $this->loadAatLevels(
            $programAavAat->pluck('fk_aat')->filter()->unique()->map(fn($id) => (int) $id)->values()->all(),
            $universityId
        );

        $matrix = [];
        foreach ($programAavAat as $row) {
            $p = (int) $row->fk_programme;
            $aav = (int) $row->fk_aav;
            $aat = (int) $row->fk_aat;
            $matrix[$p][$aav][$aat] = [
                'contribution' => $row->contribution,
            ];

            if ($row->contribution !== null && (($aatLevels[$aat] ?? null) === null)) {
                $this->pushAnomaly(
                    $collector,
                    $this->makeAnomaly(
                        self::CODE_MISSING_AAT_LEVEL,
                        $ueId,
                        $p,
                        null,
                        'warning',
                        'Une contribution AAV->AAT est renseignee mais le niveau de contribution du AAT est absent.',
                        [
                            'aav_id' => $aav,
                            'aav_code' => $aavRows->get($aav)->code ?? null,
                            'aat_id' => $aat,
                        ]
                    )
                );
            }
        }

        // Rule #7 (global UE scope): an AAV of the UE is anomalous only if it has
        // no contribution to any AAT declared on the UE, regardless of programme linkage.
        $globalAavAatRows = DB::table('aav_aat')
            ->select('fk_aav', 'fk_aat')
            ->where('university_id', $universityId)
            ->whereIn('fk_aav', empty($ueAavIds) ? [-1] : $ueAavIds)
            ->whereIn('fk_aat', empty($ueAatIds) ? [-1] : $ueAatIds)
            ->get();

        $aavHasAtLeastOneAatContribution = [];
        foreach ($globalAavAatRows as $row) {
            $aav = (int) $row->fk_aav;
            $aavHasAtLeastOneAatContribution[$aav] = true;
        }

        $impactedAavsRaw = collect($ueAavIds)
            ->filter(fn($aavId) => !isset($aavHasAtLeastOneAatContribution[(int) $aavId]))
            ->map(function ($aavId) use ($aavRows) {
                $aavId = (int) $aavId;
                return [
                    'aav_id' => $aavId,
                    'aav_code' => $aavRows->get($aavId)->code ?? null,
                    'aav_name' => $aavRows->get($aavId)->name ?? null,
                    'source' => 'vise',
                ];
            })
            ->values()
            ->all();

        // Also consider prerequisite AAVs as anomalous if they have no AAT contribution at all.
        $prereqAavRowsById = $prereqRows;
        $prereqAatLinkedIds = DB::table('aav_aat')
            ->where('university_id', $universityId)
            ->whereIn('fk_aav', empty($uePrereqIds) ? [-1] : $uePrereqIds)
            ->pluck('fk_aav')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $prereqAatLinkedLookup = array_flip($prereqAatLinkedIds);

        $missingPrereqAavsRaw = collect($uePrereqIds)
            ->filter(fn($aavId) => !isset($prereqAatLinkedLookup[(int) $aavId]))
            ->map(function ($aavId) use ($prereqAavRowsById) {
                $aavId = (int) $aavId;
                return [
                    'aav_id' => $aavId,
                    'aav_code' => $prereqAavRowsById->get($aavId)->code ?? null,
                    'aav_name' => $prereqAavRowsById->get($aavId)->name ?? null,
                    'source' => 'prerequis',
                ];
            })
            ->values()
            ->all();

        $impactedAavsRaw = array_values(array_merge($impactedAavsRaw, $missingPrereqAavsRaw));

        if (!empty($impactedAavsRaw)) {
            $impactedAavLabels = collect($impactedAavsRaw)
                ->map(function ($pair) use ($aavRows) {
                    $aavId = (int) ($pair['aav_id'] ?? 0);
                    $code = trim((string) ($pair['aav_code'] ?? ''));
                    $name = trim((string) ($pair['aav_name'] ?? $aavRows->get($aavId)->name ?? ''));

                    if ($code !== '' && $name !== '') {
                        return "{$code} - {$name}";
                    }
                    if ($code !== '') {
                        return $code;
                    }
                    if ($name !== '') {
                        return $name;
                    }
                    return $aavId > 0 ? "#{$aavId}" : null;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $aavText = !empty($impactedAavLabels)
                ? ' AAV impacte(s): ' . implode(', ', $impactedAavLabels) . '.'
                : '';

            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_MISSING_AAV_AAT_CONTRIBUTION,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Certains AAV (vises ou prerequis) de cette UE n ont aucune contribution vers des AAT.' . $aavText,
                    [
                        'missing_count' => count($impactedAavsRaw),
                        'missing_aavs' => $impactedAavsRaw,
                        'impacted_aavs' => $impactedAavLabels,
                    ]
                )
            );
        }

        // Rule #9 (global UE scope): coherence between UE->AAT and AAV->AAT,
        // regardless of programme linkage.
        $incoherentNoAavContribution = [];
        $incoherentAatNotDeclared = [];

        $globalAatWithContributionLookup = [];
        foreach ($globalAavAatRows as $row) {
            $globalAatWithContributionLookup[(int) $row->fk_aat] = true;
        }

        foreach ($ueAatIds as $aatId) {
            $aatId = (int) $aatId;
            if (isset($globalAatWithContributionLookup[$aatId])) {
                continue;
            }
            $incoherentNoAavContribution[] = [
                'aat_id' => $aatId,
                'aat_code' => $aatRowsById->get($aatId)->code ?? null,
                'aat_name' => $aatRowsById->get($aatId)->name ?? null,
            ];
        }

        $ueAatLookup = array_flip($ueAatIds);
        foreach ($globalAavAatRows as $row) {
            $aatId = (int) $row->fk_aat;
            if (isset($ueAatLookup[$aatId])) {
                continue;
            }
            $aavId = (int) $row->fk_aav;
            $incoherentAatNotDeclared[] = [
                'aav_id' => $aavId,
                'aav_code' => $aavRows->get($aavId)->code ?? null,
                'aat_id' => $aatId,
                'aat_code' => $aatRowsById->get($aatId)->code ?? null,
                'aat_name' => $aatRowsById->get($aatId)->name ?? null,
            ];
        }

        $totalIncoherent = count($incoherentNoAavContribution) + count($incoherentAatNotDeclared);
        if ($totalIncoherent > 0) {
            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_INCOHERENT_AAT_CONTRIBUTION,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'L UE declare contribuer a un AAT mais aucun AAV de l UE ne contribue a cet AAT.',
                    [
                        'total_cases' => $totalIncoherent,
                        'declared_ue_but_no_aav_cases' => $incoherentNoAavContribution,
                        'aav_to_aat_not_declared_in_ue_cases' => $incoherentAatNotDeclared,
                    ]
                )
            );
        }

        // Rule #1: global marker if one or many anomalies exist for this UE.
        if (!empty($collector)) {
            $this->pushAnomaly(
                $collector,
                $this->makeAnomaly(
                    self::CODE_HAS_ANOMALY,
                    $ueId,
                    null,
                    null,
                    'warning',
                    'Cette UE presente une ou plusieurs anomalies.',
                    [
                        'anomaly_count' => count($collector),
                    ]
                )
            );
        }

        return array_values($collector);
    }

    private function loadProgrammeContexts(int $ueId, int $universityId): array
    {
        return DB::table('ue_programme as up')
            ->leftJoin('pro_semester as ps', function ($join) use ($universityId) {
                $join->on('ps.id', '=', 'up.fk_semester')
                    ->where('ps.university_id', '=', $universityId);
            })
            ->select(
                'up.fk_programme as program_id',
                'up.fk_semester as semester_id',
                'ps.semester as semester_number'
            )
            ->where('up.university_id', $universityId)
            ->where('up.fk_unite_enseignement', $ueId)
            ->distinct()
            ->get()
            ->map(fn($row) => [
                'program_id' => (int) $row->program_id,
                'semester_id' => $row->semester_id !== null ? (int) $row->semester_id : null,
                'semester_number' => $row->semester_number !== null ? (int) $row->semester_number : null,
            ])
            ->values()
            ->all();
    }

    private function loadUEAavIds(int $ueId, int $universityId): array
    {
        return DB::table('aavue_vise')
            ->where('university_id', $universityId)
            ->where('fk_unite_enseignement', $ueId)
            ->pluck('fk_acquis_apprentissage_vise')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadUEPrereqIds(int $ueId, int $universityId): array
    {
        return DB::table('aavue_prerequis')
            ->where('university_id', $universityId)
            ->where('fk_unite_enseignement', $ueId)
            ->pluck('fk_acquis_apprentissage_prerequis')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadUEPrereqUERows(int $ueId, int $universityId): Collection
    {
        return DB::table('ue_prerequis as up')
            ->join('unite_enseignement as ue', function ($join) use ($universityId) {
                $join->on('ue.id', '=', 'up.fk_UE_prerequis')
                    ->where('ue.university_id', '=', $universityId);
            })
            ->where('up.university_id', $universityId)
            ->where('up.fk_UE_parent', $ueId)
            ->select('ue.id', 'ue.code', 'ue.name')
            ->distinct()
            ->get();
    }

    private function loadUEAatIds(int $ueId, int $universityId): array
    {
        return DB::table('ue_aat')
            ->where('university_id', $universityId)
            ->where('fk_ue', $ueId)
            ->pluck('fk_aat')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadProgramPrereqIds(int $programId, int $universityId): array
    {
        return DB::table('aavpro_prerequis')
            ->where('university_id', $universityId)
            ->where('fk_programme', $programId)
            ->pluck('fk_acquis_apprentissage_prerequis')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadPreviousSemesterAavIds(int $programId, ?int $semesterNumber, int $universityId): array
    {
        if ($semesterNumber === null) {
            return [];
        }

        return DB::table('ue_programme as up')
            ->join('pro_semester as ps', function ($join) use ($universityId) {
                $join->on('ps.id', '=', 'up.fk_semester')
                    ->where('ps.university_id', '=', $universityId);
            })
            ->join('aavue_vise as av', function ($join) use ($universityId) {
                $join->on('av.fk_unite_enseignement', '=', 'up.fk_unite_enseignement')
                    ->where('av.university_id', '=', $universityId);
            })
            ->where('up.university_id', $universityId)
            ->where('up.fk_programme', $programId)
            ->where('ps.semester', '<', $semesterNumber)
            ->pluck('av.fk_acquis_apprentissage_vise')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadPreviousSemesterUEIds(int $programId, ?int $semesterNumber, int $universityId): array
    {
        if ($semesterNumber === null) {
            return [];
        }

        return DB::table('ue_programme as up')
            ->join('pro_semester as ps', function ($join) use ($universityId) {
                $join->on('ps.id', '=', 'up.fk_semester')
                    ->where('ps.university_id', '=', $universityId);
            })
            ->where('up.university_id', $universityId)
            ->where('up.fk_programme', $programId)
            ->where('ps.semester', '<', $semesterNumber)
            ->pluck('up.fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadAavRows(array $aavIds, int $universityId): Collection
    {
        if (empty($aavIds)) {
            return collect();
        }

        return DB::table('acquis_apprentissage_vise')
            ->select('id', 'code', 'name')
            ->where('university_id', $universityId)
            ->whereIn('id', $aavIds)
            ->get();
    }

    private function loadProgramAavAatRows(array $programIds, array $ueAavIds, int $universityId): Collection
    {
        if (empty($programIds) || empty($ueAavIds)) {
            return collect();
        }

        return DB::table('aav_aat')
            ->select('fk_programme', 'fk_aav', 'fk_aat', 'contribution')
            ->where('university_id', $universityId)
            ->whereIn('fk_programme', $programIds)
            ->whereIn('fk_aav', $ueAavIds)
            ->get();
    }

    private function loadAatLevels(array $aatIds, int $universityId): array
    {
        if (empty($aatIds)) {
            return [];
        }

        return DB::table('acquis_apprentissage_terminaux')
            ->select('id', 'level_contribution')
            ->where('university_id', $universityId)
            ->whereIn('id', $aatIds)
            ->get()
            ->mapWithKeys(fn($row) => [(int) $row->id => $row->level_contribution])
            ->all();
    }

    private function recomputeEmptySemesterAnomalies(int $universityId): int
    {
        return DB::transaction(function () use ($universityId) {
            DB::table('anomalies')
                ->where('university_id', $universityId)
                ->where('code', self::CODE_MISSING_SEMESTER)
                ->whereNull('ue_id')
                ->delete();

            $emptySemesters = DB::table('pro_semester as ps')
                ->join('programme as p', function ($join) use ($universityId) {
                    $join->on('p.id', '=', 'ps.fk_programme')
                        ->where('p.university_id', '=', $universityId);
                })
                ->leftJoin('ue_programme as up', function ($join) use ($universityId) {
                    $join->on('up.fk_semester', '=', 'ps.id')
                        ->where('up.university_id', '=', $universityId);
                })
                ->where('ps.university_id', $universityId)
                ->groupBy('ps.id', 'ps.fk_programme', 'ps.semester', 'p.code', 'p.name')
                ->havingRaw('COUNT(up.id) = 0')
                ->select(
                    'ps.id as semester_id',
                    'ps.fk_programme as program_id',
                    'ps.semester as semester_number',
                    'p.code as program_code',
                    'p.name as program_name'
                )
                ->get();

            if ($emptySemesters->isEmpty()) {
                return 0;
            }

            $now = now();
            $rows = [];
            foreach ($emptySemesters as $row) {
                $programLabel = trim(implode(' - ', array_filter([
                    $row->program_code,
                    $row->program_name,
                ])));
                if ($programLabel === '') {
                    $programLabel = (string) $row->program_id;
                }

                $rows[] = [
                    'ue_id' => null,
                    'program_id' => (int) $row->program_id,
                    'semester_id' => (int) $row->semester_id,
                    'university_id' => $universityId,
                    'code' => self::CODE_MISSING_SEMESTER,
                    'severity' => 'error',
                    'message' => "Le semestre {$row->semester_number} du programme {$programLabel} ne contient aucune UE.",
                    'details' => json_encode([
                        'program_id' => (int) $row->program_id,
                        'program_code' => $row->program_code,
                        'program_name' => $row->program_name,
                        'semester_id' => (int) $row->semester_id,
                        'semester_number' => (int) $row->semester_number,
                    ]),
                    'is_resolved' => false,
                    'detected_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('anomalies')->insert($rows);

            return count($rows);
        });
    }

    private function persistForUE(int $ueId, int $universityId, array $anomalies): void
    {
        DB::transaction(function () use ($ueId, $universityId, $anomalies) {
            DB::table('anomalies')
                ->where('university_id', $universityId)
                ->where('ue_id', $ueId)
                ->delete();

            if (empty($anomalies)) {
                return;
            }

            $now = now();
            $rows = [];
            foreach ($anomalies as $anomaly) {
                $rows[] = [
                    'ue_id' => $anomaly['ue_id'],
                    'program_id' => $anomaly['program_id'],
                    'semester_id' => $anomaly['semester_id'],
                    'university_id' => $universityId,
                    'code' => $anomaly['code'],
                    'severity' => $anomaly['severity'],
                    'message' => $anomaly['message'],
                    'details' => empty($anomaly['details']) ? null : json_encode($anomaly['details']),
                    'is_resolved' => false,
                    'detected_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('anomalies')->insert($rows);
        });
    }

    private function makeAnomaly(
        string $code,
        ?int $ueId,
        ?int $programId,
        ?int $semesterId,
        string $severity,
        string $message,
        array $details = []
    ): array {
        return [
            'code' => $code,
            'ue_id' => $ueId,
            'program_id' => $programId,
            'semester_id' => $semesterId,
            'severity' => $severity,
            'message' => $message,
            'details' => $details,
        ];
    }

    private function pushAnomaly(array &$collector, array $anomaly): void
    {
        $signature = implode('|', [
            $anomaly['code'] ?? '',
            (string) ($anomaly['ue_id'] ?? ''),
            (string) ($anomaly['program_id'] ?? ''),
            (string) ($anomaly['semester_id'] ?? ''),
            md5(json_encode($anomaly['details'] ?? [])),
        ]);

        $collector[$signature] = $anomaly;
    }

    private function maxSeverity(string $a, string $b): string
    {
        $weights = [
            'info' => 1,
            'warning' => 2,
            'error' => 3,
        ];

        return ($weights[$b] ?? 1) > ($weights[$a] ?? 1) ? $b : $a;
    }
}
