<?php

namespace App\Exports;

use App\Models\Programme;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProgExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Programme::all(['code', 'name']);
    }

    public function headings(): array
    {
        return ['Code', 'Nom du programme'];
    }
}
