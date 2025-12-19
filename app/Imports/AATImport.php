<?php

namespace App\Imports;

use Illuminate\Support\Collection;

class AATImport extends ExcelBlockImport
{
    private int $startRow;
    private int $endRow;
    private array $columnMap;

    public function __construct(int $startRow, int $endRow, array $columnMap)
    {
        $this->startRow  = $startRow;
        $this->endRow    = $endRow;
        $this->columnMap = $columnMap;
    }

    public function collection(Collection $rows)
    {
        $this->parsedData = $this->readBlockVertical(
            $rows,
            $this->startRow,
            $this->endRow,
            $this->columnMap
        );
    }
}
