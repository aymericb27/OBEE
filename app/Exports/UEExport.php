<?php

namespace App\Exports;

use App\Models\UniteEnseignement;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UEExport
{
    protected $ue;

    public function __construct($ueId)
    {
        $this->ue = UniteEnseignement::with([
            'aat',
            'pro',
            'aavvise',
            'prerequis'
        ])->findOrFail($ueId);
    }

    public function download()
    {
        // 1. Charger ton fichier modèle
        $template = storage_path('app/templates/model_import_ue.xlsx');
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        // ------------------------------------
        // 2. REMPLIR LE FICHIER
        // ------------------------------------

        // UE
        $sheet->setCellValue('D4', $this->ue->code);
        $sheet->setCellValue('D5', $this->ue->name);
        $sheet->setCellValue('D6', trim(strip_tags($this->ue->description)));
        $sheet->setCellValue('D7', $this->ue->ects);

        // AAT UE
        $col = 'D';
        foreach ($this->ue->aat as $aat) {
            $sheet->setCellValue($col . '8', $aat->code);
            $sheet->setCellValue($col . '9', $aat->name);
            $sheet->setCellValue($col . '10', $aat->pivot->contribution);
            $col++;
        }

        // Programmes
        $col = 'D';
        foreach ($this->ue->pro as $p) {
            $sheet->setCellValue($col . '11', $p->code);
            $sheet->setCellValue($col . '12', $p->name);
            $sheet->setCellValue($col . '13', $p->pivot->semester);
            $col++;
        }

        // AAV
        $col = 'D';
        foreach ($this->ue->aavvise as $aav) {
            $sheet->setCellValue($col . '14', $aav->code);
            $sheet->setCellValue($col . '15', $aav->name);
            $sheet->setCellValue($col . '16', $aav->aat->code ?? '');
            $sheet->setCellValue($col . '17', $aav->aat->name ?? '');
            $sheet->setCellValue($col . '18', $aav->contribution ?? '');
            $col++;
        }

        // Prérequis
        $col = 'D';
        foreach ($this->ue->prerequis as $pre) {
            $sheet->setCellValue($col . '19', $pre->code);
            $sheet->setCellValue($col . '20', $pre->name);
            $col++;
        }

        // ------------------------------------
        // 3. Télécharger le fichier rempli
        // ------------------------------------
        $filename = "UE_{$this->ue->code}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=\"{$filename}\""
        ]);
    }
}
