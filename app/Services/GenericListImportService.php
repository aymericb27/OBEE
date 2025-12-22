<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class GenericListImportService
{
    public function extract($file, array $config): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $start = max(1, $config['startRow']);
        $end   = $config['endRow'];

        $map = $config['columns'] ?? [];

        $results = [];

        for ($r = $start; $r <= $end; $r++) {
            $row = [];
            foreach ($map as $fieldKey => $columnLetter) {
                
                $cell = $sheet->getCell($columnLetter.$r)->getValue();
                $row[$fieldKey] = trim((string) $cell);
            }

            if (!empty(array_filter($row))) {
                $results[] = $row;
            }
        }

        return $results;
    }
}
