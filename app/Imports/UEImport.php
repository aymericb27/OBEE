<?php

namespace App\Imports;

use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UEImport implements ToCollection
{
    public array $parsedData = [];

    public function collection(Collection $rows)
    {
        // --- UE (valeurs uniques) ---
        $identifiant = $this->firstValueAfterLabel($this->getRow($rows, 3));
        $intitule    = $this->firstValueAfterLabel($this->getRow($rows, 4));
        $description = $this->firstValueAfterLabel($this->getRow($rows, 5));
        $credits     = $this->firstValueAfterLabel($this->getRow($rows, 6));

        // --- PROGRAMMES (plusieurs colonnes) ---
        $programmes = $this->parseHorizontalBlock(
            $this->getRow($rows, 7),   // identifiants
            $this->getRow($rows, 8),   // libellés
            $this->getRow($rows, 9)    // semestres
        );

        // --- AAV (plusieurs colonnes) ---
        $aavs = $this->parseHorizontalBlock(
            $this->getRow($rows, 10),  // identifiants
            $this->getRow($rows, 11),  // libellés
            null                       // pas de semestre
        );

        // --- PRÉREQUIS (plusieurs colonnes) ---
        $prerequis = $this->parseHorizontalBlock(
            $this->getRow($rows, 12),  // identifiants
            $this->getRow($rows, 13),  // libellés
            null                       // pas de semestre
        );

        $this->parsedData = [
            'ue' => [
                'identifiant' => $identifiant,
                'intitule'    => $intitule,
                'description' => $description,
                'credits'     => $credits,
            ],
            'programmes' => $programmes,
            'aavs'       => $aavs,
            'prerequis'  => $prerequis,
        ];
    }

    /**
     * Récupère une ligne du fichier sous forme de tableau (même si elle n'existe pas).
     */
    private function getRow(Collection $rows, int $index): array
    {
        if (!isset($rows[$index])) {
            return [];
        }

        $row = $rows[$index];

        // Si c'est une Collection, on la convertit en array
        if ($row instanceof Collection) {
            return $row->toArray();
        }

        return (array) $row;
    }

    /**
     * Récupère la première valeur non vide après la colonne 0 (le label).
     */
    private function firstValueAfterLabel(array $row)
    {
        foreach ($row as $index => $cell) {
            if ($index === 0) continue;
            if ($cell !== null && $cell !== '') {
                return $cell;
            }
        }
        return null;
    }

    /**
     * Fonction générique pour lire un "bloc horizontal" :
     * - $rowId : ligne des identifiants
     * - $rowLib : ligne des libellés (optionnel)
     * - $rowSem : ligne des semestres (optionnel)
     * - $startCol : index de colonne où commencer (2 = après le label + une colonne vide)
     */
    private function parseHorizontalBlock(
        array $rowId,
        ?array $rowLib = null,
        ?array $rowSem = null,
        int $startCol = 2
    ): array {
        $results = [];

        $maxCols = max(
            count($rowId),
            $rowLib ? count($rowLib) : 0,
            $rowSem ? count($rowSem) : 0,
        );

        for ($col = $startCol; $col < $maxCols; $col++) {

            $id  = $rowId[$col]  ?? null;
            $lib = $rowLib[$col] ?? null;
            $sem = $rowSem[$col] ?? null;

            // Pas d'identifiant → on ignore la colonne
            if ($id === null || $id === '') {
                continue;
            }

            $item = [
                'identifiant' => $id,
            ];

            if ($rowLib !== null && $lib !== null && $lib !== '') {
                $item['libelle'] = $lib;
            }

            if ($rowSem !== null && $sem !== null && $sem !== '') {

                // Toujours travailler sur une string
                $semStr = trim((string) $sem);

                // On coupe sur virgule OU point → "1,2" ou "1.2" → ["1","2"]
                $parts = preg_split('/[,.]/', $semStr);

                // On enlève les vides éventuels
                $parts = array_values(array_filter($parts, fn($v) => $v !== ''));

                // On cast en int quand c'est uniquement des chiffres
                $semArray = array_map(
                    fn($v) => ctype_digit($v) ? (int) $v : $v,
                    $parts
                );

                // Perso je te conseille de toujours garder un tableau, même s'il n'y a qu'un semestre
                $item['semestres'] = $semArray;
            }


            $results[] = $item;
        }

        return $results;
    }
}
