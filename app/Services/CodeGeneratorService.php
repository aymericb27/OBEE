<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CodeGeneratorService
{
    /**
     * Generate a code of the form PREFIX + numeric suffix (e.g. AAV001).
     *
     * @param class-string<Model> $modelClass
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

        $codes = $query->pluck('code');
        $strictPrefixPattern = '/^' . preg_quote($prefix, '/') . '(\\d+)$/';

        $maxSuffix = 0;
        foreach ($codes as $code) {
            if (!is_string($code)) {
                continue;
            }

            if (!preg_match($strictPrefixPattern, $code, $matches)) {
                continue;
            }

            $suffix = (int) ($matches[1] ?? 0);
            if ($suffix > $maxSuffix) {
                $maxSuffix = $suffix;
            }
        }

        $nextNumber = $maxSuffix + 1;

        do {
            $candidate = $prefix . str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
            $nextNumber++;

            $exists = $modelClass::query()
                ->when($scopedByUniversity, function ($query) {
                    $universityId = Auth::user()?->university_id;
                    if ($universityId) {
                        $query->where('university_id', $universityId);
                    }
                })
                ->where('code', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }

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
        return $this->generate(\App\Models\AcquisApprentissageVise::class, 'PRE', 3, true);
    }

    public function nextProgramme(): string
    {
        return $this->generate(\App\Models\Programme::class, 'PRO', 3, true);
    }
}