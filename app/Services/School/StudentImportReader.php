<?php

namespace App\Services\School;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportReader
{
    public const HEADER = ['Name', 'Email', 'Date of Birth'];

    /** The earlier template. Its Username column is ignored now that usernames are generated. */
    private const LEGACY_HEADER = ['Name', 'Email', 'Username', 'Date of Birth'];

    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    /** @return array<int,array{0:?string,1:?string,2:?string}> name, email, dob keyed by sheet row - 2 */
    public function read(Worksheet $sheet): array
    {
        $rows = $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().$sheet->getHighestDataRow());
        // A trailing "(...)" is ignored so "Date of Birth (YYYY-MM)" still matches.
        $normalize = static fn ($value) => strtolower(trim(preg_replace(['/\s+/u', '/\s*\(.*\)$/'], [' ', ''], str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], ['', ' '], (string) $value))));
        $header = array_map($normalize, $this->trimEmptyColumns(array_shift($rows) ?? []));
        $legacy = $header === array_map($normalize, self::LEGACY_HEADER);
        if (!$legacy && $header !== array_map($normalize, self::HEADER)) {
            throw new InvalidArgumentException('Invalid student template headers. Expected: '.implode(' | ', self::HEADER).'. Download the student template and keep these columns in this order.');
        }
        $columns = $legacy ? count(self::LEGACY_HEADER) : count(self::HEADER);
        $dobColumn = Coordinate::stringFromColumnIndex($columns);

        $students = [];
        foreach ($rows as $index => $row) {
            $row = $this->trimEmptyColumns($row);
            if (!$row) continue;
            if (count($row) > $columns) {
                throw new InvalidArgumentException('Row '.($index + 2).': Unexpected data after the Date of Birth column. Use only the student template columns.');
            }
            $row = array_pad($row, $columns, null);
            // A real date cell is read back as its display text, which may be US
            // month-first (4/5/2013 for 5 April), so take the date itself instead.
            $coordinate = $dobColumn.($index + 2);
            if ($sheet->cellExists($coordinate)) {
                $cell = $sheet->getCell($coordinate);
                if (is_numeric($cell->getValue()) && Date::isDateTime($cell)) {
                    $row[$columns - 1] = Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
                }
            }
            if ($legacy) array_splice($row, 2, 1);
            $students[$index] = $row;
            if (count($students) > 1000) {
                throw new InvalidArgumentException('A maximum of 1000 student rows is allowed per upload. Blank rows are not counted. Split the students into smaller files.');
            }
        }
        return $students;
    }

    /**
     * A date of birth typed in any common way, as the stored YYYY-MM, or null
     * when it is not a real date. 05/04/2013 is read day-first (the Singapore
     * order) unless only the second number can be a day.
     */
    public static function dob(?string $value): ?string
    {
        $value = preg_replace('/(?:T|\s+)\d{1,2}:\d{2}(?::\d{2})?(?:\.\d+)?$/', '', trim((string) $value));
        $day = 1;
        if (preg_match('/^\d{5}$/', $value)) {
            // An Excel date number that lost its date format, e.g. 41408.
            $date = Date::excelToDateTimeObject((int) $value);
            [$year, $month] = [(int) $date->format('Y'), (int) $date->format('n')];
        } elseif (preg_match('#^(\d{4})[-/.](\d{1,2})(?:[-/.](\d{1,2}))?$#', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) ($m[3] ?? 1)];
        } elseif (preg_match('#^(\d{1,2})[-/.](\d{1,2})[-/.](\d{2}|\d{4})$#', $value, $m)) {
            [$first, $second, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            [$day, $month] = $second > 12 && $first <= 12 ? [$second, $first] : [$first, $second];
        } elseif (preg_match('#^(\d{1,2})[-/.](\d{4})$#', $value, $m)) {
            [$month, $year] = [(int) $m[1], (int) $m[2]];
        } elseif (preg_match('/^(?:(\d{1,2})[\s-]+)?([a-z]{3,9})\.?(?:[\s-]+(\d{1,2}),?)?[\s,-]+(\d{2}|\d{4})$/i', $value, $m)
            && isset(self::MONTHS[strtolower(substr($m[2], 0, 3))])) {
            // May 2013, May-13, 14 May 2013, 14-May-2013, May 14, 2013
            $month = self::MONTHS[strtolower(substr($m[2], 0, 3))];
            [$day, $year] = [(int) ($m[1] ?: ($m[3] ?: 1)), (int) $m[4]];
        } else {
            return null;
        }
        if ($year < 100) $year += 2000;
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d', $year, $month) : null;
    }

    private function trimEmptyColumns(array $row): array
    {
        while ($row && trim((string) end($row)) === '') array_pop($row);
        return $row;
    }
}
