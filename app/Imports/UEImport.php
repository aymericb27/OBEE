<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UEImport implements ToCollection
{
    public array $parsedData = [];

    public function collection(Collection $rows)
    {
        // -------------------------------
        // UE (valeurs uniques)
        // lignes 3 à 6, valeur en colonne C (index 2)
        // -------------------------------
        $identifiant = $this->firstValueAfterLabel($this->row($rows, 3));
        $intitule    = $this->firstValueAfterLabel($this->row($rows, 4));
        $description = $this->firstValueAfterLabel($this->row($rows, 5));
        $credits     = $this->firstValueAfterLabel($this->row($rows, 6));

        // -------------------------------
        // AAT de l’UE
        // ligne 7 : "Acquis d'apprentissage terminal", "identifiant", [données à partir de C]
        // ligne 8 : "", "niveau de contribution...", [données à partir de C]
        // => on ignore A (0) ET B (1) → startCol = 2
        // -------------------------------
        $ueAATs = $this->parseHorizontal(
            $this->row($rows, 7),   // identifiants AAT
            $this->row($rows, 8),  // libellés
            $this->row($rows, 9),   // niveaux de contribution
            startCol: 2,
            thirdKey: 'contribution'
        );

        // -------------------------------
        // Programmes
        // ligne 9  : "programme", "identifiant", [PRO1, PRO2, ... à partir de C]
        // ligne 10 : "", "libellé",      [libellés à partir de C]
        // ligne 11 : "", "Semestre...",  [semestres à partir de C]
        // => on ignore A (0) ET B (1) → startCol = 2
        // -------------------------------
        $programmes = $this->parseHorizontal(
            $this->row($rows, 10),   // identifiants PRO
            $this->row($rows, 11),  // libellés
            $this->row($rows, 12),  // semestres (1 seul chiffre)
            startCol: 2,
            thirdKey: 'semestre'
        );

        // -------------------------------
        // AAV
        // ligne 12 : "Acquis d'apprentissage visé", "identifiant", [AAV... à partir de C]
        // ligne 13 : "", "libellé", [libellés à partir de C]
        // => données à partir de la colonne C → startCol = 2
        // -------------------------------
        $aavs = $this->parseHorizontal(
            $this->row($rows, 13),  // identifiants AAV
            $this->row($rows, 14),  // libellés
            null,
            startCol: 2
        );

        // -------------------------------
        // AAT pour chaque AAV
        // ligne 14 : "", "acquis d'apprentissage terminal", "identifiant", [AAT... à partir de D]
        //             index 0 = vide, 1 = texte, 2 = "identifiant"
        //             → données à partir de colonne D → index 3
        // ligne 15 : "", "", "niveau de contribution...", [niv. à partir de D]
        // -------------------------------
        $aavAATs = $this->parseHorizontal(
            $this->row($rows, 15),  // identifiants AAT liés aux AAV
            $this->row($rows, 16),  // libellés
            $this->row($rows, 17),  // niveaux
            startCol: 3,
            thirdKey: 'contribution'
        );

        // -------------------------------
        // Fusion AAV + AAT (format à plat)
        // aavs[i] ↔ aavAATs[i] (même colonne)
        // -------------------------------
        $mergedAAVs = [];
        foreach ($aavs as $i => $aav) {
            $aat = $aavAATs[$i] ?? null;

            $mergedAAVs[] = [
                'code'    => $aav['code'] ?? null,
                'name'        => $aav['libelle'] ?? null,
                'AATCode' => $aat['code'] ?? null,
                'contribution'   => $aat['contribution'] ?? null,
                'AATName' => $aat['libelle'] ?? null,
            ];
        }

        // -------------------------------
        // Prérequis
        // ligne 16 : "Prérequis", "identifiant", [PRE... à partir de C]
        // ligne 17 : "", "libellé", [libellés à partir de C]
        // => startCol = 2
        // -------------------------------
        $prerequis = $this->parseHorizontal(
            $this->row($rows, 18),  // identifiants PRE
            $this->row($rows, 19),  // libellés
            null,
            startCol: 2
        );

        // -------------------------------
        // Résultat final
        // -------------------------------
        $this->parsedData = [
            'ue'         => [
                'code' => $identifiant,
                'name'    => $intitule,
                'description' => $description,
                'ects'     => $credits,
            ],
            'aats'    => $ueAATs,
            'programmes' => $programmes,
            'aavs'       => $mergedAAVs,
            'prerequis'  => $prerequis,
        ];
    }

    /**
     * Récupère une ligne sous forme de tableau.
     */
    private function row(Collection $rows, int $index): array
    {
        if (!isset($rows[$index])) {
            return [];
        }

        $row = $rows[$index];

        return $row instanceof Collection
            ? $row->toArray()
            : (array) $row;
    }

    /**
     * Première valeur non vide après la colonne 0 (A).
     * (UE : valeur en colonne C dans ton modèle, mais comme B est vide, on tombe bien sur C)
     */
    private function firstValueAfterLabel(array $row)
    {
        foreach ($row as $i => $cell) {
            if ($i === 0) continue;
            if ($cell !== null && $cell !== '') {
                return $cell;
            }
        }
        return null;
    }

    /**
     * Parsing horizontal générique pour les tableaux.
     *
     * - $rowId   : ligne des identifiants
     * - $rowLib  : ligne des libellés (optionnelle)
     * - $row3    : ligne d’une 3e info (semestre, contribution, niveau...) (optionnelle)
     * - $startCol: index de la première colonne de données (2 = colonne C, 3 = colonne D)
     * - $thirdKey: nom de la clé pour la 3e info ("semestre", "contribution", "niveau", ...)
     */
    private function parseHorizontal(
        array $rowId,
        ?array $rowLib = null,
        ?array $row3 = null,
        int $startCol = 2,
        string $thirdKey = 'value'
    ): array {
        $results = [];

        $maxCols = max(
            count($rowId),
            $rowLib ? count($rowLib) : 0,
            $row3 ? count($row3) : 0,
        );

        for ($col = $startCol; $col < $maxCols; $col++) {
            $id  = $rowId[$col]  ?? null;
            $lib = $rowLib[$col] ?? null;
            $v3  = $row3[$col]   ?? null;

            // pas d'identifiant → colonne ignorée
            if ($id === null || $id === '') {
                continue;
            }

            $item = [
                'code' => $id,
            ];

            if ($rowLib !== null && $lib !== null && $lib !== '') {
                $item['libelle'] = $lib;
            }

            if ($row3 !== null && $v3 !== null && $v3 !== '') {
                $v = trim((string) $v3);
                $item[$thirdKey] = ctype_digit($v) ? (int) $v : $v;
            }

            $results[] = $item;
        }

        return $results;
    }
}
