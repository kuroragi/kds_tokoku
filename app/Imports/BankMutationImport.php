<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class BankMutationImport implements ToArray, WithHeadingRow, WithCalculatedFormulas
{
    protected array $rows = [];

    public function array(array $rows): void
    {
        $this->rows = $rows;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
