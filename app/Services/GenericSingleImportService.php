<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Validation\ValidationException;

class GenericSingleImportService
{
    private $sheet;

    public function extract($file, array $config): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $this->sheet = $spreadsheet->getActiveSheet();

        $type = $config['type'] ?? 'UE';
        $errors = [];

        // 1) Objet principal
        $main = $this->extractMainByType($type, $config['cells'][$config['type']] ?? [], $errors);

        // 2) Liens (listes horizontales)
        $links = [
            'ues'        => $this->extractLineLists($config['ue'] ?? [], 'UE', $errors),
            'aats'       => $this->extractLineLists($config['aat'] ?? [], 'AAT', $errors),
            'aavs'       => $this->extractLineLists($config['aav'] ?? [], 'AAV', $errors),
            'prerequis'  => $this->extractLineLists($config['prerequis'] ?? [], 'Prérequis', $errors),
            'programmes' => $this->extractProgrammeLists($config['programmes'] ?? [], 'Programmes', $errors),
        ];

        // 3) Si erreurs => ValidationException Laravel
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'type'  => $type,
            'main'  => $main,
            'links' => $links,
        ];
    }

    private function extractMainByType(string $type, array $cells, array &$errors): array
    {
        return match ($type) {
            'UE'  => $this->extractUECells($cells, $errors),
            'AAT' => $this->extractSimpleCells($cells, 'aat', [
                'code' => ['label' => 'Sigle AAT', 'required' => false],
                'name' => ['label' => 'Libellé AAT', 'required' => true],
                'description' => ['label' => 'Description AAT', 'required' => false],
            ], $errors),
            'AAV' => $this->extractSimpleCells($cells, 'aav', [
                'code' => ['label' => 'Sigle AAV', 'required' => false],
                'name' => ['label' => 'Libellé AAV', 'required' => true],
                'description' => ['label' => 'Description AAV', 'required' => false],
            ], $errors),
            'PRE' => $this->extractSimpleCells($cells, 'prerequis_main', [
                'code' => ['label' => 'Sigle prérequis', 'required' => false],
                'name' => ['label' => 'Libellé prérequis', 'required' => true],
            ], $errors),
            'PRO' => $this->extractSimpleCells($cells, 'programme', [
                'code' => ['label' => 'Code programme', 'required' => false],
                'name' => ['label' => 'Libellé programme', 'required' => true],
                'semestre' => ['label' => 'Semestre programme', 'required' => false],
                'ects' => ['label' => 'ECTS programme', 'required' => false],
            ], $errors),
            default => [],
        };
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

    private function extractSimpleCells(array $cells, string $prefix, array $schema, array &$errors): array
    {
        $out = [];
        foreach ($schema as $key => $meta) {
            $cellRef = $cells[$key] ?? null;
            $out[$key] = $this->readCellRequired(
                $cellRef,
                "{$prefix}.{$key}",
                $meta['label'],
                $errors,
                (bool)$meta['required']
            );
        }
        return $out;
    }

    private function readCellRequired(?string $ref, string $key, string $label, array &$errors, bool $required = false): ?string
    {
        if (!$ref || trim($ref) === '') {
            if ($required) $errors[$key][] = "Veuillez indiquer une cellule pour $label.";
            return null;
        }

        $val = trim((string) $this->sheet->getCell($ref)->getCalculatedValue());

        if ($val === '') {
            $errors[$key][] = "La cellule $ref ($label) est vide.";
            return null;
        }

        return $val;
    }

    private function readCell(?string $ref): ?string
    {
        if (!$ref || trim($ref) === '') return null;
        $val = trim((string) $this->sheet->getCell($ref)->getCalculatedValue());
        return $val === '' ? null : $val;
    }

    /**
     * Ex: block = ['code' => 'C9', 'libelle' => 'C11', 'contribution' => 'C12']
     * Retour: [ ['code'=>..., 'libelle'=>..., 'contribution'=>...], ... ]
     */
    private function extractLineLists(array $block, string $label, array &$errors): array
    {
        $rows = [];
        foreach (['code', 'libelle', 'contribution'] as $key) {
            $cell = trim($block[$key] ?? '');
            if ($cell === '') continue;

            $first = $this->readCell($cell);
            if ($first === null) {
                $errors["$label.$key"][] = "La cellule $cell ($label - $key) est vide.";
                continue;
            }

            $rows[$key] = $this->readHorizontalUntilEmpty($cell);
        }

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
     * Programmes: optionnel si ton front les envoie différemment
     * Ici on supporte: ['code'=>'C5','libelle'=>'C6','semestre'=>'C7']
     */
    private function extractProgrammeLists(array $block, string $label, array &$errors): array
    {
        $rows = [];

        foreach (['code', 'libelle', 'semestre'] as $key) {
            $cell = trim($block[$key] ?? '');
            if ($cell === '') continue;

            $first = $this->readCell($cell);
            if ($first === null) {
                $errors["$label.$key"][] = "La cellule $cell ($label - $key) est vide.";
                continue;
            }

            $rows[$key] = $this->readHorizontalUntilEmpty($cell);
        }

        $max = 0;
        foreach ($rows as $arr) $max = max($max, count($arr));

        $results = [];
        for ($i = 0; $i < $max; $i++) {
            $results[] = [
                'code' => $rows['code'][$i] ?? null,
                'libelle' => $rows['libelle'][$i] ?? null,
                'semestre' => isset($rows['semestre'][$i]) ? (int)$rows['semestre'][$i] : null,
            ];
        }

        return $results;
    }

    private function readHorizontalUntilEmpty(string $startCell): array
    {
        $startCell = strtoupper(trim($startCell));
        [$colLetters, $row] = Coordinate::coordinateFromString($startCell);

        $row = (int) $row;
        $colIndex = Coordinate::columnIndexFromString($colLetters); // 1-based

        $values = [];
        while (true) {
            $addr = Coordinate::stringFromColumnIndex($colIndex) . $row;
            $val = trim((string) $this->sheet->getCell($addr)->getCalculatedValue());

            if ($val === '') break;

            $values[] = $val;
            $colIndex++;
        }

        return $values;
    }
}
