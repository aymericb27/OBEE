<?php

namespace App\Exports;

use App\Models\UniteEnseignement as UE;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UEExport implements FromCollection, WithHeadings, WithEvents
{
    protected array $filter;
    protected array $select;
    /** Liste ordonnée des "table.col as alias" calculée 1 seule fois */
    protected array $computedSelects = [];

    public function __construct(array $filterOrSelect = [], array $select = [])
    {
        if ($select === [] && $this->looksLikeSelect($filterOrSelect)) {
            $this->filter = [];
            $this->select = $filterOrSelect;
        } else {
            $this->filter = $filterOrSelect ?? [];
            $this->select = $select ?? [];
        }

        $this->select = $this->normalizeSelect($this->select);

        // 👇 On calcule ici (dispo pour headings() ET collection())
        $this->computedSelects = $this->computeSelectsAliases();

        Log::info('SELECT reçu', $this->select);
        Log::info('FILTER reçu', $this->filter);
        Log::info('ALIASES calculés', $this->computedSelects);
    }

    public function collection()
    {
        $query = UE::query();

        // --- 1) Colonnes de base (toujours la même liste que headings()) ---
        $selects = $this->computedSelects;

        // --- 2) JOINs en fonction des groupes cochés ---

        // Programme
        if ($this->hasAnyChecked($this->select['prog'] ?? [])) {
            $query->leftJoin('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->leftJoin('programme', 'programme.id', '=', 'ue_programme.fk_programme');
        }

        // AAV visés
        if ($this->hasAnyChecked($this->select['aavvise'] ?? [])) {
            $query->leftJoin('aavue_vise', 'aavue_vise.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->leftJoin('acquis_apprentissage_vise as aavvise', 'aavvise.id', '=', 'aavue_vise.fk_acquis_apprentissage_vise');
        }

        // AAV prérequis (même table, autre pivot)
        if ($this->hasAnyChecked($this->select['aavprerequis'] ?? [])) {
            $query->leftJoin('aavue_prerequis', 'aavue_prerequis.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->leftJoin('acquis_apprentissage_vise as aavprerequis', 'aavprerequis.id', '=', 'aavue_prerequis.fk_acquis_apprentissage_prerequis');
        }

        // AAT (terminaux) — one-to-many depuis AAV visé
        if ($this->hasAnyChecked($this->select['aat'] ?? [])) {
            // Nécessite déjà aavvise (si pas joint plus haut, on le joint vite fait)
            if (!$this->hasAnyChecked($this->select['aavvise'] ?? [])) {
                $query->leftJoin('aavue_vise', 'aavue_vise.fk_unite_enseignement', '=', 'unite_enseignement.id')
                    ->leftJoin('acquis_apprentissage_vise as aavvise', 'aavvise.id', '=', 'aavue_vise.fk_acquis_apprentissage_vise');
            }
            $query->leftJoin('acquis_apprentissage_terminaux as aat', 'aat.id', '=', 'aavvise.fk_acquis_apprentissage_terminal');
        }

        // --- 3) Filtres ---
        $program = $this->filter['program'] ?? null;
        if ($program && $program !== 'all') {
            if (!$this->hasAnyChecked($this->select['prog'] ?? [])) {
                $query->leftJoin('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
                    ->leftJoin('programme', 'programme.id', '=', 'ue_programme.fk_programme');
            }
            $query->where('programme.id', $program);
        }

        $semester = $this->filter['semester'] ?? null;
        if (!empty($semester)) {
            $query->where('unite_enseignement.semestre', $semester);
        }

        // --- 4) Exécution ---
        if (!empty($selects)) {
            $query->select($selects);
        }
        Log::info('SQL reçu', ['query' => $query->toSql()]);

        return $query->get();
    }

    public function headings(): array
    {
        // Map alias => libellé humain
        $labels = [
            // UE
            'ue_code'         => 'Code unité d’enseignement',
            'ue_name'         => 'Nom unité d’enseignement',
            'ue_description'  => 'Description unité d’enseignement',
            'ue_ects'         => 'ECTS',
            'ue_date_begin'   => 'date de début',
            'ue_date_end'     => 'date de end',
            'ue_semestre'     => 'Semestre',

            // Programme
            'programme_code'  => 'Code du programme',
            'programme_name'  => 'Nom du programme',

            // AAV visés
            'aavvise_code'        => 'Code acquis d’apprentissage visé',
            'aavvise_name'        => 'Nom acquis d’apprentissage visé',
            'aavvise_description' => 'Description acquis d’apprentissage visé',

            // AAV prérequis
            'aavprerequis_code'        => 'Code acquis d’apprentissage prérequis',
            'aavprerequis_name'        => 'Nom acquis d’apprentissage prérequis',
            'aavprerequis_description' => 'Description acquis d’apprentissage prérequis',

            // AAT
            'aat_code'         => 'Code acquis d’apprentissage terminal',
            'aat_name'         => 'Nom acquis d’apprentissage terminal',
            'aat_description'  => 'Description acquis d’apprentissage terminal',
        ];

        $headers = [];
        foreach ($this->computedSelects as $col) {
            // Ex: "unite_enseignement.name as ue_name" -> "ue_name"
            if (preg_match('/\bas\s+(\w+)$/i', $col, $m)) {
                $alias = $m[1];
                $headers[] = $labels[$alias] ?? $alias;
            }
        }
        return $headers;
    }

    /* ==================== Helpers ==================== */

    /**
     * Construit la même liste de "table.col as alias" que celle utilisée par collection(),
     * mais SANS toucher au Query Builder (donc utilisable dans headings()).
     */
    private function computeSelectsAliases(): array
    {
        $selects = [];

        // 1) UE — si rien coché: code + name
        $ueSelect = $this->select['ue'] ?? [];
        if ($this->hasAnyChecked($ueSelect)) {
            $ueCols = $this->pickColsAliases($ueSelect, 'ue_');
        } else {
            // aucun champ coché => défaut minimal
            $ueCols = [
                'unite_enseignement.code as ue_code',
                'unite_enseignement.name as ue_name',
            ];
        }

        $selects = array_merge($selects, array_values($ueCols));
        Log::info('Select UE', ['query' => $ueCols]);

        // 2) Programme
        if ($this->hasAnyChecked($this->select['prog'] ?? [])) {
            $selects = array_merge($selects, $this->pickColsAliases($this->select['prog'], 'programme_'));
        }

        // 3) AAV visés
        if ($this->hasAnyChecked($this->select['aavvise'] ?? [])) {
            $selects = array_merge($selects, $this->pickColsAliases($this->select['aavvise'], 'aavvise_'));
        }

        // 4) AAV prérequis
        if ($this->hasAnyChecked($this->select['aavprerequis'] ?? [])) {
            $selects = array_merge($selects, $this->pickColsAliases($this->select['aavprerequis'], 'aavprerequis_'));
        }

        // 5) AAT
        if ($this->hasAnyChecked($this->select['aat'] ?? [])) {
            $selects = array_merge($selects, $this->pickColsAliases($this->select['aat'], 'aat_'));
        }

        return $selects;
    }

    private function looksLikeSelect(array $arr): bool
    {
        $keys = array_keys($arr);
        return array_intersect($keys, ['ue', 'prog', 'aavvise', 'aavprerequis', 'aat']) !== [];
    }

    private function normalizeSelect(array $select): array
    {
        $truthy = fn($v) => in_array($v, [true, 1, '1', 'true', 'on'], true);
        foreach ($select as $group => $fields) {
            if (!is_array($fields)) continue;
            foreach ($fields as $k => $v) {
                $select[$group][$k] = $truthy($v);
            }
        }
        return $select;
    }

    private function hasAnyChecked(array $group): bool
    {
        foreach ($group as $k => $v) {
            if ($k === 'all') continue;
            if (in_array($v, [true, 1, '1', 'true', 'on'], true)) return true;
        }
        return false;
    }

    /**
     * Retourne une liste de "table.col as alias" pour un groupe donné, à partir des alias connus.
     * NB: on ne touche pas aux tables ici; on ne produit que les ALIAS dans le même ordre que collection().
     */
    private function pickColsAliases(array $group, string $prefix, array $defaults = []): array
    {
        // mapping alias -> "table.col as alias"
        $map = [
            // UE
            'ue_code'        => 'unite_enseignement.code as ue_code',
            'ue_name'        => 'unite_enseignement.name as ue_name',
            'ue_description' => 'unite_enseignement.description as ue_description',
            'ue_ects'        => 'unite_enseignement.ects as ue_ects',
            'ue_date_begin'  => 'unite_enseignement.date_begin as ue_date_begin',
            'ue_date_end'    => 'unite_enseignement.date_end as ue_date_end',
            'ue_semestre'    => 'unite_enseignement.semestre as ue_semestre',


            // Programme
            'programme_code' => 'programme.code as programme_code',
            'programme_name' => 'programme.name as programme_name',

            // AAV visés
            'aavvise_code'        => 'aavvise.code as aavvise_code',
            'aavvise_name'        => 'aavvise.name as aavvise_name',
            'aavvise_description' => 'aavvise.description as aavvise_description',

            // AAV prérequis
            'aavprerequis_code'        => 'aavprerequis.code as aavprerequis_code',
            'aavprerequis_name'        => 'aavprerequis.name as aavprerequis_name',
            'aavprerequis_description' => 'aavprerequis.description as aavprerequis_description',

            // AAT
            'aat_code'        => 'aat.code as aat_code',
            'aat_name'        => 'aat.name as aat_name',
            'aat_description' => 'aat.description as aat_description',
        ];

        // Si defaults fournis (cas UE quand rien coché), on les renvoie tout de suite
        if (!empty($defaults)) {
            return $defaults;
        }

        $out = [];
        foreach ($group as $field => $checked) {
            if ($field === 'all' || !$checked) continue;
            $alias = $prefix . $field; // ex: programme_name
            if (isset($map[$alias])) {
                $out[] = $map[$alias];
            }
        }
        return $out;
    }
    public static function afterSheet(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();

        // Largeurs automatiques (boucle sur les colonnes existantes)
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Optionnel : hauteur et style des en-têtes
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getDefaultRowDimension()->setRowHeight(20);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet'],
        ];
    }
}
