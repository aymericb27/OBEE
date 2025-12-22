<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class GenericSingleImportService
{
    private $sheet;

    /**
     * Charge le fichier, initialise la sheet, et lance l'extraction générique
     */
    public function extract($file, array $config): array
    {
        // 📂 Charger le fichier Excel ici (plus besoin de constructeur)
        $spreadsheet = IOFactory::load($file->getRealPath());
        $this->sheet = $spreadsheet->getActiveSheet();

        // 📌 Extraction UE simple via cells
        $ue = [];
        if (isset($config['cells'])) {
            $ue = $this->extractUECells($config['cells']);
        }

        // 📌 Extraction générique pour les zones (prerequis, aav, aat, etc.)
        $prerequis = $this->extractConfigBlock($config['prerequis'] ?? []);
        $aav       = $this->extractConfigBlock($config['aav'] ?? []);
        $aat       = $this->extractConfigBlock($config['aat'] ?? []);

        return [
            'ue'        => $ue,
            'prerequis' => $prerequis,
            'aavs'       => $aav,
            'aats'       => $aat,
        ];
    }

    /**
     * Extrait les champs UE simples via des cellules uniques
     */
    private function extractUECells(array $cells): array
    {
        return [
            'code'        => $this->readCell($cells['code'] ?? null),
            'name'        => $this->readCell($cells['name'] ?? null),
            'ects'        => (int) $this->readCell($cells['ects'] ?? null),
            'description' => $this->readCell($cells['description'] ?? null),
        ];
    }

    /**
     * Lit une cellule si elle existe
     */
    private function readCell(?string $ref): ?string
    {
        if (!$ref || trim($ref) === '') return null;
        return trim((string) $this->sheet->getCell($ref)->getValue());
    }

    /**
     * Reçoit une zone config avec base + extra
     */
    private function extractConfigBlock(array $block): array
    {
        if (!isset($block['base'])) {
            return [];
        }

        $base = $this->extractBlock($block['base']);

        $extras = [];
        foreach ($block['extra'] ?? [] as $extraConf) {
            $extras[] = $this->extractBlock($extraConf);
        }

        return $this->mergeBaseAndExtras($base, $extras);
    }

    /**
     * Fusionne : chaque index du base absorbe les extras correspondants
     */
    private function mergeBaseAndExtras(array $base, array $extras): array
    {
        $count = count($base);
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            $row = $base[$i];

            foreach ($extras as $block) {
                if (isset($block[$i])) {
                    // ex : [ "libelle" => "..." ] → fusion
                    $row = array_merge($row, $block[$i]);
                }
            }

            $results[] = $row;
        }

        return $results;
    }

    /**
     * Gère un bloc : mode single ou horizontal
     */
    private function extractBlock(array $conf): array
    {
        return match($conf['mode']) {
            'single'     => $this->extractSingle($conf),
            'horizontal' => $this->extractHorizontal($conf),
            default      => [],
        };
    }

    /**
     * MODE SINGLE avec séparateur
     */
    private function extractSingle(array $conf): array
    {
        if (empty($conf['cell'])) return []; // sécurité

        $cell = trim((string) $this->sheet->getCell($conf['cell'])->getValue());
        if (!$cell) return [];

        $sep = $conf['separator'] ?: ',';

        $values = preg_split('/[' . preg_quote($sep, '/') . '\n]+/', $cell);

        return array_values(array_map(function ($val) use ($conf) {
            return [
                $conf['type'] => trim($val),
            ];
        }, array_filter($values)));
    }

    /**
     * MODE HORIZONTAL : récupère plusieurs colonnes sur une ligne
     */
    private function extractHorizontal(array $conf): array
    {
        if (empty($conf['row']) || empty($conf['startCol']) || empty($conf['endCol'])) {
            return []; // sécurité
        }

        $row   = (int) $conf['row'];
        $start = $this->colToIndex($conf['startCol']);
        $end   = $this->colToIndex($conf['endCol']);

        $results = [];

        for ($col = $start; $col <= $end; $col++) {
            $value = trim((string) $this->sheet->getCellByColumnAndRow($col + 1, $row)->getValue());

            if ($value !== '') {
                $results[] = [
                    $conf['type'] => $value
                ];
            }
        }

        return $results;
    }

    /**
     * Conversion lettre Excel → index numérique zéro-based
     */
    private function colToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }

        return $index - 1;
    }
}
