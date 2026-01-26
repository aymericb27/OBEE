<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CodeGeneratorService
{
    /**
     * Génère un code du type PREFIX + 3 digits (ex: AAV001)
     *
     * @param class-string<Model> $modelClass  Ex: AcquisApprentissageVise::class
     * @param string $prefix                  Ex: 'AAV', 'AAT', 'PRE', 'PRO'
     * @param int $pad                        Ex: 3 => 001
     * @param bool $scopedByUniversity        true => filtre university_id
     */
    public function generate(
        string $modelClass,
        string $prefix,
        int $pad = 3,
        bool $scopedByUniversity = true
    ): string {
        $query = $modelClass::query()
            ->where('code', 'like', $prefix . '%');

        if ($scopedByUniversity) {
            $universityId = Auth::user()?->university_id;
            if ($universityId) {
                $query->where('university_id', $universityId);
            }
        }

        // Trie par longueur puis par valeur pour éviter les effets de tri lexicographique
        $last = $query->orderByRaw('LENGTH(code) DESC')
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;

        if ($last && is_string($last->code)) {
            $suffix = substr($last->code, strlen($prefix)); // ex: "012"
            $n = (int) preg_replace('/\D/', '', $suffix);   // garde que les chiffres
            $nextNumber = $n + 1;
        }

        return $prefix . str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
    }

    // Helpers dédiés (plus lisibles dans ton storeUE)
    public function nextAAV(): string
    {
        return $this->generate(\App\Models\AcquisApprentissageVise::class, 'AAV', 3, true);
    }
    public function nextUE(): string
    {
        return $this->generate(\App\Models\UniteEnseignement::class, 'UE', 3, true);
    }

    public function nextAAT(): string
    {
        return $this->generate(\App\Models\AcquisApprentissageTerminaux::class, 'AAT', 3, true);
    }

    public function nextPrerequis(): string
    {
        // si tes prérequis sont aussi dans AcquisApprentissageVise:
        return $this->generate(\App\Models\AcquisApprentissageVise::class, 'PRE', 3, true);
    }

    public function nextProgramme(): string
    {
        return $this->generate(\App\Models\Programme::class, 'PRO', 3, true);
    }
}
