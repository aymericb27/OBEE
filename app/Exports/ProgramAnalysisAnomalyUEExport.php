<?php

namespace App\Exports;

use App\Models\Programme;
use App\Models\UniteEnseignement;
use App\Services\UEAnomalyService;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramAnalysisAnomalyUEExport
{
    private $programme;
    private ?string $selectedAnomalyCode;

    public function __construct(
        private int $programId,
        private int $universityId,
        ?string $selectedAnomalyCode = null
    ) {
        $normalizedCode = trim((string) $selectedAnomalyCode);
        $this->selectedAnomalyCode = $normalizedCode !== '' ? $normalizedCode : null;

        $this->programme = Programme::select('id', 'code', 'name')
            ->where('id', $this->programId)
            ->where('university_id', $this->universityId)
            ->firstOrFail();
    }

    public function download()
    {
        $rows = $this->buildRows();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('UE anomalies');

        $sheet->setCellValue('A1', 'Liste des UE avec anomalies');
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A3', 'Programme');
        $sheet->setCellValue('B3', trim((string) $this->programme->code . ' - ' . (string) $this->programme->name));

        // Compat avec la demande précédente (ancienne position template).
        $sheet->setCellValue('A5', 'UE');
        $sheet->setCellValue('B5', 'Anomalies');

        $startRow = 6;
        if (empty($rows)) {
            $sheet->setCellValue('A' . $startRow, 'Aucune UE avec anomalie pour ce filtre.');
            $sheet->mergeCells("A{$startRow}:B{$startRow}");
        } else {
            foreach ($rows as $index => $row) {
                $excelRow = $startRow + $index;
                $sheet->setCellValue('A' . $excelRow, $row['ue']);
                $sheet->setCellValue('B' . $excelRow, $row['anomalies']);
            }
        }

        $endRow = empty($rows) ? $startRow : ($startRow + count($rows) - 1);

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle('A5:B5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEFEFEF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A5:B{$endRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B6:B{$endRow}")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getColumnDimension('A')->setWidth(42);
        $sheet->getColumnDimension('B')->setWidth(100);

        for ($r = 6; $r <= $endRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(35);
        }

        $programCode = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $this->programme->code);
        if ($programCode === null || $programCode === '') {
            $programCode = "programme_{$this->programId}";
        }

        $anomalyPart = $this->selectedAnomalyCode !== null
            ? preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $this->selectedAnomalyCode)
            : 'toutes';
        if ($anomalyPart === null || $anomalyPart === '') {
            $anomalyPart = 'toutes';
        }

        $filename = "anomalies_ues_{$programCode}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildRows(): array
    {
        ['semester_rows' => $semesterRows, 'all_ue_ids' => $allUeIds] = $this->loadSemesterUeEntriesForAnalysis();
        $anomalyCodesByUe = $this->loadAnomalyCodesByUe($allUeIds);

        $rows = [];
        $seenUe = [];

        foreach ($semesterRows as $semesterRow) {
            foreach (($semesterRow['ues'] ?? []) as $ue) {
                $ueId = (int) ($ue['id'] ?? 0);
                if ($ueId <= 0 || isset($seenUe[$ueId])) {
                    continue;
                }

                $codes = $anomalyCodesByUe[$ueId] ?? [];
                if (empty($codes)) {
                    continue;
                }

                if ($this->selectedAnomalyCode !== null && !in_array($this->selectedAnomalyCode, $codes, true)) {
                    continue;
                }

                $codesToExport = $this->selectedAnomalyCode !== null
                    ? [$this->selectedAnomalyCode]
                    : $codes;

                $anomalyLabels = collect($codesToExport)
                    ->map(fn($code) => $this->anomalyCodeLabel((string) $code))
                    ->filter(fn($label) => trim((string) $label) !== '')
                    ->unique()
                    ->values()
                    ->all();

                $ueCode = trim((string) ($ue['code'] ?? ''));
                $ueName = trim((string) ($ue['name'] ?? ''));
                $ueLabel = trim(implode(' - ', array_filter([$ueCode, $ueName])));
                if ($ueLabel === '') {
                    $ueLabel = "UE #{$ueId}";
                }

                $rows[] = [
                    'ue' => $ueLabel,
                    'anomalies' => implode(' | ', $anomalyLabels),
                ];

                $seenUe[$ueId] = true;
            }
        }

        return $rows;
    }

    private function loadSemesterUeEntriesForAnalysis(): array
    {
        $semesters = DB::table('pro_semester')
            ->select('id', 'semester')
            ->where('fk_programme', $this->programId)
            ->where('university_id', $this->universityId)
            ->orderBy('semester')
            ->get();

        $semesterRows = [];
        $allUeIds = [];
        foreach ($semesters as $semester) {
            $ues = $this->getUEBySemester((int) $semester->id);
            $ueEntries = collect($ues)
                ->flatMap(function ($ue) {
                    $items = collect([[
                        'id' => (int) $ue->id,
                        'code' => (string) $ue->code,
                        'name' => (string) $ue->name,
                    ]]);

                    $children = collect($ue->children ?? [])
                        ->map(fn($child) => [
                            'id' => (int) $child->id,
                            'code' => (string) $child->code,
                            'name' => (string) $child->name,
                        ]);

                    return $items->merge($children);
                })
                ->unique('id')
                ->values()
                ->all();

            $semesterRows[] = [
                'id' => (int) $semester->id,
                'number' => (int) $semester->semester,
                'ues' => $ueEntries,
            ];

            $allUeIds = array_merge(
                $allUeIds,
                collect($ueEntries)->pluck('id')->map(fn($id) => (int) $id)->all()
            );
        }

        return [
            'semester_rows' => $semesterRows,
            'all_ue_ids' => collect($allUeIds)->unique()->values()->all(),
        ];
    }

    private function getUEBySemester(int $proSemesterId)
    {
        return UniteEnseignement::select(
            'unite_enseignement.id',
            'unite_enseignement.code',
            'unite_enseignement.name',
            'unite_enseignement.ects',
            'ue_programme.display_order'
        )
            ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('ue_programme.fk_programme', $this->programId)
            ->where('ue_programme.fk_semester', $proSemesterId)
            ->where('ue_programme.university_id', $this->universityId)
            ->whereNotIn('unite_enseignement.id', function ($query) {
                $query->select('fk_ue_child')
                    ->from('element_constitutif')
                    ->where('university_id', $this->universityId);
            })
            ->orderBy('ue_programme.display_order')
            ->orderBy('ue_programme.id')
            ->with('children')
            ->get();
    }

    private function loadAnomalyCodesByUe(array $ueIds): array
    {
        $anomalyRows = DB::table('anomalies')
            ->select('ue_id', 'code')
            ->where('university_id', $this->universityId)
            ->where('is_resolved', false)
            ->where('code', '!=', UEAnomalyService::CODE_HAS_ANOMALY)
            ->whereIn('ue_id', empty($ueIds) ? [-1] : $ueIds)
            ->get();

        return $anomalyRows
            ->groupBy('ue_id')
            ->map(fn($group) => collect($group)->pluck('code')->unique()->values()->all())
            ->all();
    }
    private function anomalyCodeLabel(string $code): string
    {
        return match ($code) {
            UEAnomalyService::CODE_PREREQ_AS_UE => 'Erreur de prerequis (Une UE a des prérequis UE renseigné mais aucun prérequis AAV)',
            UEAnomalyService::CODE_PREREQ_OUTSIDE_ALLOWED => 'Erreur de prerequis (les prerequis ne sont pas des AAV d un semestre precedent)',
            UEAnomalyService::CODE_PREREQ_UE_OUTSIDE_ALLOWED => 'Erreur de prerequis (les UE prerequises ne sont pas dans un semestre precedent)',
            UEAnomalyService::CODE_PREREQ_AAV_NOT_IN_PREREQ_UE => 'Erreur de coherence prerequis UE/AAV',
            UEAnomalyService::CODE_EMPTY_AAV_LIST => 'Erreur de donnees (liste des AAV vide)',
            UEAnomalyService::CODE_EMPTY_CREDITS => 'Erreur de credit',
            UEAnomalyService::CODE_MISSING_SEMESTER => "Erreur d'affectation de semestre",
            UEAnomalyService::CODE_MISSING_AAV_AAT_CONTRIBUTION => 'Erreur de contribution',
            UEAnomalyService::CODE_MISSING_AAT_LEVEL => 'Erreur de niveau de contribution',
            UEAnomalyService::CODE_INCOHERENT_AAT_CONTRIBUTION => 'Erreur de coherence de contribution (les AAV ne contribuent pas a un AAT de l UE)',
            UEAnomalyService::CODE_NOT_IN_ANY_PROGRAM => "Erreur d'affectation au programme",
            default => $code,
        };
    }
}


