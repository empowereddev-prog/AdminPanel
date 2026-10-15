<?php

namespace App\Services\School;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportReader
{
    public const HEADER = ['Name', 'Email', 'Username', 'Date of Birth (YYYY-MM)'];

    public function read(Worksheet $sheet): array
    {
        $rows = $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().$sheet->getHighestDataRow());
        $header = $this->trimEmptyColumns(array_shift($rows) ?? []);
        $normalize = static fn ($value) => strtolower(trim(preg_replace('/\s+/u', ' ', str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], ['', ' '], (string) $value))));
        if (array_map($normalize, $header) !== array_map($normalize, self::HEADER)) {
            throw new InvalidArgumentException('Invalid student template headers. Expected: '.implode(' | ', self::HEADER).'. Download the student template and keep these columns in this order.');
        }

        $students = [];
        foreach ($rows as $index => $row) {
            $row = $this->trimEmptyColumns($row);
            if (!$row) continue;
            if (count($row) > count(self::HEADER)) {
                throw new InvalidArgumentException('Row '.($index + 2).': Unexpected data after the Date of Birth column. Use only the four student template columns.');
            }
            $students[$index] = array_pad($row, count(self::HEADER), null);
            if (count($students) > 1000) {
                throw new InvalidArgumentException('A maximum of 1000 student rows is allowed per upload. Blank rows are not counted. Split the students into smaller files.');
            }
        }
        return $students;
    }

    private function trimEmptyColumns(array $row): array
    {
        while ($row && trim((string) end($row)) === '') array_pop($row);
        return $row;
    }
}