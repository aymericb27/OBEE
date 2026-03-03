<?php

namespace App\Exports;

use App\Models\AcquisApprentissageTerminaux;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class AATExport
{
    protected $aat;

    public function __construct($aatID)
    {
        $this->aat = AcquisApprentissageTerminaux::with([
            'aav'
        ])->findOrFail($aatID);
    }
    public function download()
    {
        // 1. Charger ton fichier modèle
        $template = resource_path('templates/model_import_aat.xlsx');
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        // ------------------------------------
        // 2. REMPLIR LE FICHIER
        // ------------------------------------

        // UE
        $sheet->setCellValue('B4', $this->aat->code);
        $sheet->setCellValue('B5', $this->aat->name);
        $sheet->setCellValue('B6', trim(strip_tags($this->aat->description)));

        $col = 9;
        foreach ($this->aat->aav as $aav) {
            $sheet->setCellValue('A' . $col, $aav->code);
            $sheet->setCellValue('B' . $col, $aav->name);
            $sheet->setCellValue('C' . $col, $aav->pivot->contribution);
            $col++;
        }

        // ------------------------------------
        // 3. Télécharger le fichier rempli
        // ------------------------------------
        $filename = "AAT_{$this->aat->code}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=\"{$filename}\""
        ]);
    }
}
