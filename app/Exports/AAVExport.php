<?php

namespace App\Exports;

use App\Models\AcquisApprentissageVise;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class AAVExport
{
    protected $aav;

    public function __construct($aavID)
    {
        $this->aav = AcquisApprentissageVise::with([
            'aats',
            'prerequis',
            'aavvise',
        ])->findOrFail($aavID);
    }
    public function download()
    {
        // 1. Charger ton fichier modèle
        $template = storage_path('app/templates/model_import_AAV.xlsx');
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        // ------------------------------------
        // 2. REMPLIR LE FICHIER
        // ------------------------------------

        // UE
        $sheet->setCellValue('B4', $this->aav->code);
        $sheet->setCellValue('B5', $this->aav->name);
        $sheet->setCellValue(
            'B6',
            trim(strip_tags($this->aav->description))
        );
        $col = 9;
        foreach ($this->aav->aats as $aat) {
            $sheet->setCellValue('A' . $col, $aat->code);
            $sheet->setCellValue('B' . $col, $aat->name);
            $sheet->setCellValue('C' . $col, $aat->pivot->contribution);
            $col++;
        }
        $col = 9;
        foreach ($this->aav->prerequis as $ue) {
            $sheet->setCellValue('D' . $col, $ue->code);
            $sheet->setCellValue('E' . $col, $ue->name);
            $col++;
        }

        $col = 9;
        foreach ($this->aav->aavvise as $ue) {
            $sheet->setCellValue('F' . $col, $ue->code);
            $sheet->setCellValue('G' . $col, $ue->name);
            $col++;
        }
        // ------------------------------------
        // 3. Télécharger le fichier rempli
        // ------------------------------------
        $filename = "aaT_{$this->aav->code}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=\"{$filename}\""
        ]);
    }
}
