<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UEImport implements ToCollection
{
    public array $parsedData = [];

    public function collection(Collection $rows)
    {
        /**
         * ATTENTION :
         * - Excel structuré
         * - valeurs utiles en colonne C (index 2)
         */

        // -------------------------------
        // UE (colonne C uniquement)
        // -------------------------------
        $code  = $this->cell($rows, 0); // sigle
        $name  = $this->cell($rows, 1); // intitulé
        $ects  = $this->cell($rows, 7); // crédits

        // -------------------------------
        // AAT (une cellule, colonne C)
        // -------------------------------

        $aats = $this->parseAATCell(
            $this->cell($rows, 11) // "Contribution annoncée aux AAT"
        );

        // -------------------------------
        // AAV (plusieurs colonnes à partir de C)
        // -------------------------------
        $aavs = $this->parseAAVRow(
            $this->row($rows, 13), // ligne "AAV"
            startCol: 2
        );

        // -------------------------------
        // Résultat FINAL (UNIQUEMENT ce qui est utile)
        // -------------------------------
        $this->parsedData = [
            'ue' => [
                'code' => $code,
                'name' => $name,
                'ects' => (int) $ects,
            ],
            'aats' => $aats,
            'aavs' => $aavs,
        ];
    }

    /**
     * Récupère la valeur de la colonne C d’une ligne donnée.
     */
    private function cell(Collection $rows, int $rowIndex): ?string
    {
        if (!isset($rows[$rowIndex][2])) {
            return null;
        }

        return trim((string) $rows[$rowIndex][2]);
    }

    /**
     * Récupère une ligne complète sous forme de tableau.
     */
    private function row(Collection $rows, int $index): array
    {
        if (!isset($rows[$index])) {
            return [];
        }

        return $rows[$index] instanceof Collection
            ? $rows[$index]->toArray()
            : (array) $rows[$index];
    }

    /**
     * AAT : "AAT1, AAT2, AAT3"
     */
    /**
     * AAT : séparés par virgule OU retour à la ligne
     */
    private function parseAATCell(?string $cell): array
    {
        if (!$cell) {
            return [];
        }

        return collect(
            preg_split('/[\n\r,]+/', $cell)
        )
            ->map(fn($v) => trim($v))
            ->filter()
            ->map(fn($code) => ['code' => $code])
            ->values()
            ->toArray();
    }


    /**
     * AAV : plusieurs colonnes à partir de C
     */
    private function parseAAVRow(array $row, int $startCol = 2): array
    {
        $aavs = [];

        for ($i = $startCol; $i < count($row); $i++) {
            if (empty($row[$i])) {
                continue;
            }

            $aavs[] = [
                'code' => trim((string) $row[$i]),
            ];
        }

        return $aavs;
    }
}
