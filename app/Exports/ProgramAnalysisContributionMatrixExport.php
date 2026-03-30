<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramAnalysisContributionMatrixExport
{
    private string $programCode;
    private string $programName;
    private array $aats;
    private array $rows;

    public function __construct(string $programCode, string $programName, array $aats, array $rows)
    {
        $this->programCode = trim($programCode);
        $this->programName = trim($programName);
        $this->aats = collect($aats)
            ->map(function ($aat) {
                return [
                    'id' => (int) ($aat['id'] ?? 0),
                    'code' => trim((string) ($aat['code'] ?? '')),
                    'name' => trim((string) ($aat['name'] ?? '')),
                ];
            })
            ->filter(fn($aat) => ($aat['id'] ?? 0) > 0)
            ->values()
            ->all();

        $this->rows = collect($rows)
            ->map(function ($row) {
                $contributions = $row['contributions'] ?? [];
                if (!is_array($contributions)) {
                    $contributions = [];
                }

                return [
                    'ue_id' => (int) ($row['ue_id'] ?? 0),
                    'ue_code' => trim((string) ($row['ue_code'] ?? '')),
                    'ue_name' => trim((string) ($row['ue_name'] ?? '')),
                    'aav_id' => (int) ($row['aav_id'] ?? 0),
                    'aav_code' => trim((string) ($row['aav_code'] ?? '')),
                    'aav_name' => trim((string) ($row['aav_name'] ?? '')),
                    'contributions' => $contributions,
                ];
            })
            ->filter(fn($row) => ($row['ue_id'] > 0 || $row['ue_code'] !== '') && $row['aav_code'] !== '')
            ->values()
            ->all();
    }

    public function download()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Matrice AAV-AAT');

        $programRow = 2;
        $headerRowTitle = 4;
        $headerRowCols = 5;
        $dataStartRow = 6;
        $ueCol = 'A';
        $aavCol = 'B';
        $aatStartColIndex = 3; // C
        $aatCount = count($this->aats);
        $aatEndColIndex = max($aatStartColIndex, $aatStartColIndex + $aatCount - 1);

        $titleStart = $this->columnFromIndex($aatStartColIndex);
        $titleEnd = $this->columnFromIndex($aatEndColIndex);

        $sheet->mergeCells("{$titleStart}{$headerRowTitle}:{$titleEnd}{$headerRowTitle}");
        $sheet->setCellValue("{$titleStart}{$headerRowTitle}", "Acquis d'Apprentissage terminaux du programme (AAT)");
        $sheet->setCellValue("{$ueCol}{$headerRowCols}", 'UE');
        $sheet->setCellValue("{$aavCol}{$headerRowCols}", 'AAV');

        foreach ($this->aats as $index => $aat) {
            $col = $this->columnFromIndex($aatStartColIndex + $index);
            $sheet->setCellValue("{$col}{$headerRowCols}", $aat['code'] !== '' ? $aat['code'] : ('AAT' . ($index + 1)));
        }

        $currentRow = $dataStartRow;
        $blockStartRow = $dataStartRow;
        $groupedRows = [];
        $separatorRows = [];

        foreach ($this->rows as $row) {
            $ueCode = trim((string) ($row['ue_code'] ?? ''));
            $ueKey = ($row['ue_id'] ?? 0) > 0 ? ('id:' . (int) $row['ue_id']) : ('code:' . $ueCode);
            $groupedRows[$ueKey]['ue_code'] = $ueCode !== '' ? $ueCode : ('UE#' . (int) ($row['ue_id'] ?? 0));
            $groupedRows[$ueKey]['rows'][] = $row;
        }

        if (empty($groupedRows)) {
            $sheet->setCellValue("{$aavCol}{$dataStartRow}", 'Aucune donnée pour les filtres sélectionnés.');
            $currentRow = $dataStartRow + 1;
        } else {
            $groupCount = count($groupedRows);
            $groupIndex = 0;
            foreach ($groupedRows as $group) {
                $rows = $group['rows'] ?? [];
                if (empty($rows)) {
                    $groupIndex++;
                    continue;
                }

                $blockStartRow = $currentRow;
                foreach ($rows as $row) {
                    if ($currentRow === $blockStartRow) {
                        $sheet->setCellValue("{$ueCol}{$currentRow}", (string) ($group['ue_code'] ?? ''));
                    }

                    $sheet->setCellValue("{$aavCol}{$currentRow}", (string) ($row['aav_code'] ?? ''));

                    foreach ($this->aats as $index => $aat) {
                        $col = $this->columnFromIndex($aatStartColIndex + $index);
                        $aatId = (int) ($aat['id'] ?? 0);
                        $value = $this->extractContributionValue($row['contributions'] ?? [], $aatId);
                        if ($value === null) {
                            continue;
                        }
                        $sheet->setCellValue("{$col}{$currentRow}", $value);
                        $sheet->getStyle("{$col}{$currentRow}")->getFont()->setBold(true);
                        $sheet->getStyle("{$col}{$currentRow}")->getFont()->getColor()->setARGB(Color::COLOR_RED);
                        $sheet->getStyle("{$col}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $currentRow++;
                }

                if ($currentRow - 1 > $blockStartRow) {
                    $sheet->mergeCells("{$ueCol}{$blockStartRow}:{$ueCol}" . ($currentRow - 1));
                    $sheet->getStyle("{$ueCol}{$blockStartRow}:{$ueCol}" . ($currentRow - 1))
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP);
                }

                $sheet->getStyle("{$ueCol}" . ($currentRow - 1) . ":{$titleEnd}" . ($currentRow - 1))
                    ->getBorders()
                    ->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                $groupIndex++;
                if ($groupIndex < $groupCount) {
                    $separatorRows[] = $currentRow;
                    $sheet->setCellValue("{$ueCol}{$currentRow}", '');
                    $currentRow++;
                }
            }
        }

        $tableEndRow = max($headerRowCols, $currentRow - 1);

        $sheet->getStyle("{$ueCol}{$headerRowCols}:{$titleEnd}{$headerRowCols}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEFEFEF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("{$titleStart}{$headerRowTitle}:{$titleEnd}{$headerRowTitle}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("{$ueCol}{$headerRowTitle}:{$titleEnd}{$tableEndRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("A{$programRow}", 'Programme');
        $sheet->setCellValue("B{$programRow}", $this->buildProgramLabel());
        $sheet->mergeCells("B{$programRow}:{$titleStart}{$programRow}");
        $sheet->getStyle("A{$programRow}:{$titleStart}{$programRow}")->getFont()->setBold(true);

        if (!empty($separatorRows)) {
            foreach ($separatorRows as $separatorRow) {
                $sheet->getStyle("{$ueCol}{$separatorRow}:{$titleEnd}{$separatorRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_NONE);
            }
        }

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(22);
        for ($i = $aatStartColIndex; $i <= $aatEndColIndex; $i++) {
            $sheet->getColumnDimension($this->columnFromIndex($i))->setWidth(11);
        }
        $programCode = preg_replace('/[^A-Za-z0-9_-]+/', '_', $this->programCode);
        if ($programCode === null || $programCode === '') {
            $programCode = 'programme';
        }
        $filename = "matrice_contribution_aav_aat_{$programCode}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildProgramLabel(): string
    {
        $parts = [];
        if ($this->programCode !== '') {
            $parts[] = $this->programCode;
        }
        if ($this->programName !== '') {
            $parts[] = $this->programName;
        }
        return !empty($parts) ? implode(' - ', $parts) : 'Programme';
    }

    private function extractContributionValue(array $contributions, int $aatId): ?int
    {
        $keys = [(string) $aatId, $aatId];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $contributions)) {
                continue;
            }
            $value = $contributions[$key];
            if ($value === null || $value === '') {
                return null;
            }
            if (is_numeric($value)) {
                return (int) $value;
            }
            return null;
        }
        return null;
    }

    private function columnFromIndex(int $index): string
    {
        $index = max(1, $index);
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = (int) (($index - $mod - 1) / 26);
        }
        return $letters;
    }
}
