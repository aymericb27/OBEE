<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GenericSingleImportService
{
    private $sheet;

    public function extract($file, array $config): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $this->sheet = $spreadsheet->getActiveSheet();

        $errors = [];

        $ue = [];
        if (isset($config['cells'])) {
            $ue = $this->extractUECells($config['cells'], $errors);
        }

        $prerequis = $this->extractLineLists($config['prerequis'] ?? [], 'Prérequis', $errors);
        $aav       = $this->extractLineLists($config['aav'] ?? [], 'AAV', $errors);
        $aat       = $this->extractLineLists($config['aat'] ?? [], 'AAT', $errors);

        // ✅ si erreurs => renvoie ValidationException
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return [
            'ue' => $ue,
            'prerequis' => $prerequis,
            'aavs' => $aav,
            'aats' => $aat,
        ];
    }

    private function extractUECells(array $cells, array &$errors): array
    {
        return [
            'code'        => $this->readCellRequired($cells['code'] ?? null, 'ue.code', 'Sigle UE', $errors, false),
            'name'        => $this->readCellRequired($cells['name'] ?? null, 'ue.name', 'Libellé UE', $errors, true),
            'ects'        => (int)($this->readCellRequired($cells['ects'] ?? null, 'ue.ects', 'ECTS UE', $errors, true) ?? 0),
            'description' => $this->readCellRequired($cells['description'] ?? null, 'ue.description', 'Description UE', $errors, false),
        ];
    }
    private function readCellRequired(?string $ref, string $key, string $label, array &$errors, bool $required = false): ?string
    {
        if (!$ref || trim($ref) === '') {
            if ($required) {
                $errors[$key][] = "Veuillez indiquer une cellule pour $label.";
            }
            return null;
        }

        $val = $this->sheet->getCell($ref)->getCalculatedValue();
        $val = trim((string) $val);

        // ✅ cellule fournie mais vide
        if ($val === '') {
            $errors[$key][] = "La cellule $ref ($label) est vide.";
            return null;
        }

        return $val;
    }

    private function readCell(?string $ref): ?string
    {
        if (!$ref || trim($ref) === '') return null;

        // getCalculatedValue() si tu as des formules
        $val = $this->sheet->getCell($ref)->getCalculatedValue();
        $val = trim((string) $val);

        return $val === '' ? null : $val;
    }

    /**
     * Reçoit: ['code' => 'C9', 'libelle' => 'C11', 'contribution' => 'C12']
     * Retourne: [
     *   'code' => ['...', '...'],
     *   'libelle' => ['...', '...'],
     *   'contribution' => [...]
     * ]
     *
     * Chaque champ est lu horizontalement depuis la cellule donnée jusqu'à cellule vide.
     */
    private function extractLineLists(array $block, string $label, array &$errors): array
    {
        $rows = [];
        foreach (['code', 'libelle', 'contribution'] as $key) {
            $cell = trim($block[$key] ?? '');

            if ($cell === '') continue;

            // ✅ le user a donné une cellule => on doit vérifier qu'elle n'est pas vide
            $first = $this->readCell($cell);
            if ($first === null || trim($first) === '') {
                $errors["$label.$key"][] = "La cellule $cell ($label - $key) est vide.";
                continue;
            }

            $rows[$key] = $this->readHorizontalUntilEmpty($cell);
        }

        // retour format array of objects
        $max = 0;
        foreach ($rows as $arr) $max = max($max, count($arr));

        $results = [];
        for ($i = 0; $i < $max; $i++) {
            $results[] = [
                'code' => $rows['code'][$i] ?? null,
                'libelle' => $rows['libelle'][$i] ?? null,
                'contribution' => $rows['contribution'][$i] ?? null,
            ];
        }

        return $results;
    }


    /**
     * Lit C14, D14, E14... jusqu'à tomber sur une cellule vide.
     * Retourne un tableau de strings.
     */
    private function readHorizontalUntilEmpty(string $startCell): array
    {
        $startCell = strtoupper(trim($startCell));

        // Ex: "C14" => ["C", "14"]
        [$colLetters, $row] = Coordinate::coordinateFromString($startCell);

        $row = (int) $row;
        $colIndex = Coordinate::columnIndexFromString($colLetters); // 1-based

        $values = [];
        while (true) {
            $addr = Coordinate::stringFromColumnIndex($colIndex) . $row;

            $val = $this->sheet->getCell($addr)->getCalculatedValue();
            $val = trim((string) $val);

            if ($val === '') {
                break;
            }

            $values[] = $val;
            $colIndex++;
        }

        return $values;
    }
}
