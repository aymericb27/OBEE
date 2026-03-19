<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Validation\ValidationException;

class GenericListImportService
{
    public function extract($file, array $config): array
    {
        if (!$file || !method_exists($file, 'getRealPath')) {
            throw ValidationException::withMessages([
                'file' => ["Fichier d'import invalide ou absent."],
            ]);
        }

        if (method_exists($file, 'isValid') && !$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => ["Le fichier uploadé est invalide (code: {$file->getError()})."],
            ]);
        }

        $realPath = $file->getRealPath();
        if (!$realPath || !is_file($realPath)) {
            throw ValidationException::withMessages([
                'file' => ["Le fichier temporaire n'est pas accessible sur le serveur."],
            ]);
        }

        if ((int) @filesize($realPath) <= 0) {
            throw ValidationException::withMessages([
                'file' => ["Le fichier importé est vide."],
            ]);
        }

        try {
            $spreadsheet = IOFactory::load($realPath);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => ["Impossible de lire le fichier Excel. Vérifiez qu'il est valide (.xlsx/.xls) et non corrompu."],
            ]);
        }
        $sheet = $spreadsheet->getActiveSheet();

        $type = $config['type'] ?? null;

        // ✅ mapping du type courant (UE / AAT / ...)
        $map = $config['columns'][$type] ?? [];

        // ✅ normalisation: field => ['col' => 'A', 'row' => 3|null]
        $normalized = $this->normalizeMap($map);

        // ✅ startRow = si l'utilisateur a mis des cellules (A3), on démarre à cette ligne
        $startFromMapping = $this->minRowFromMap($normalized);
        $start = $startFromMapping ?? max(1, (int)($config['startRow'] ?? 1));

        // endRow : si fourni, on le garde (c'est un numéro de ligne absolu)
        $end = (int)($config['endRow'] ?? $start);

        $results = [];
        for ($r = $start; $r <= $end; $r++) {
            $row = ['__row' => $r];

            foreach ($normalized as $fieldKey => $info) {
                $col = $info['col'];
                if (!$col) continue;

                $value = $sheet->getCell($col . $r)->getValue();
                $row[$fieldKey] = trim((string)$value);
            }

            if (!empty(array_filter($row, fn($v, $k) => $k === '__row' ? false : $v !== '', ARRAY_FILTER_USE_BOTH))) {
                $results[] = $row;
            }
        }

        return $results;
    }

    private function normalizeMap(array $map): array
    {
        $out = [];

        foreach ($map as $fieldKey => $ref) {
            $ref = trim((string)$ref);

            if ($ref === '') {
                $out[$fieldKey] = ['col' => null, 'row' => null];
                continue;
            }

            // "A" / "AA"
            if (preg_match('/^[A-Z]+$/i', $ref)) {
                $out[$fieldKey] = ['col' => strtoupper($ref), 'row' => null];
                continue;
            }

            // "A3" / "AA12"
            if (preg_match('/^([A-Z]+)(\d+)$/i', $ref, $m)) {
                $out[$fieldKey] = ['col' => strtoupper($m[1]), 'row' => (int)$m[2]];
                continue;
            }

            // invalide
            $out[$fieldKey] = ['col' => null, 'row' => null];
        }

        return $out;
    }

    private function minRowFromMap(array $normalized): ?int
    {
        $rows = [];
        foreach ($normalized as $info) {
            if (!empty($info['row'])) $rows[] = (int)$info['row'];
        }
        return $rows ? min($rows) : null;
    }
}
