<?php

namespace App\Exports;

use App\Models\Programme;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
class PROExport
{
    protected $pro;

    public function __construct($proID)
    {
        $this->pro = Programme::with([
            'ues'
        ])->findOrFail($proID);
    }
    public function download()
    {
        // 1. Charger ton fichier modèle
        $template = storage_path('app/templates/model_import_pro.xlsx');
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        // ------------------------------------
        // 2. REMPLIR LE FICHIER
        // ------------------------------------

        // UE
        $sheet->setCellValue('B4', $this->pro->code);
        $sheet->setCellValue('B5', $this->pro->name);
        $sheet->setCellValue('B6', trim(strip_tags($this->pro->description)));

        $col = 9;
        foreach ($this->pro->ues as $ue) {
            $sheet->setCellValue('A' . $col, $ue->code);
            $sheet->setCellValue('B' . $col, $ue->name);
            $col++;
        }

        // ------------------------------------
        // 3. Télécharger le fichier rempli
        // ------------------------------------
        $filename = "AAT_{$this->pro->code}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=\"{$filename}\""
        ]);
    }
}
