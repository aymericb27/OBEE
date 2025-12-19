<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

abstract class ExcelBlockImport implements ToCollection
{
    public array $parsedData = [];

    /**
     * Méthode utilitaire générique :
     * - lit un bloc d'un fichier excel
     * - mapping champ => colonne Excel (A, B, C ...)
     * - lignes Excel = 1-based (3 = troisième ligne réelle)
     */
    protected function readBlockVertical(
        Collection $rows,
        int $startRow,
        int $endRow,
        array $columnMap
    ): array {

        $results = [];

        // Ajustement Excel 1-based -> collection 0-based
        $slice = $rows->slice($startRow - 1, ($endRow - $startRow + 1));

        foreach ($slice as $row) {
            $row = $row instanceof Collection ? $row->toArray() : (array) $row;

            // Objet final pour cette ligne
            $item = [];

            foreach ($columnMap as $field => $excelColumnLetter) {

                $colIndex = $this->columnLetterToIndex($excelColumnLetter);

                $value = $row[$colIndex] ?? null;
                $value = is_string($value) ? trim($value) : $value;

                // Ne stocke pas les champs vides
                if ($value !== null && $value !== '') {
                    $item[$field] = $value;
                }
            }

            // si aucun champ n'a de valeur -> ignore ligne vide
            if (!empty($item)) {
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * Convertit lettre Excel (A, B, C...) en index numérique 0-based
     * A -> 0, B -> 1, Z -> 25, AA -> 26 etc.
     */
    private function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $length = strlen($letter);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += ord($letter[$i]) - ord('A') + 1;
        }

        return $index - 1;
    }
}
