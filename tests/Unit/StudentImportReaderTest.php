<?php

namespace Tests\Unit;

use App\Services\School\StudentImportReader;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudentImportReaderTest extends TestCase
{
    private function read(array $rows): array
    {
        $book = new Spreadsheet();
        try {
            $book->getActiveSheet()->fromArray($rows);
            return (new StudentImportReader())->read($book->getActiveSheet());
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_header_whitespace_and_blank_extra_columns_are_accepted(): void
    {
        $rows = $this->read([
            ["\xEF\xBB\xBF Name ", 'EMAIL', 'Date of Birth ', null, ''],
            ['Student Name', 'student@example.test', '2013-05', null, ''],
        ]);
        $this->assertCount(1, $rows);
        $this->assertCount(3, $rows[0]);
    }

    public function test_a_bracketed_hint_after_a_header_is_ignored(): void
    {
        $rows = $this->read([['Name', 'Email', 'Date of Birth (DD/MM/YYYY)'], ['Student Name', 'student@example.test', '14/05/2013']]);
        $this->assertSame([['Student Name', 'student@example.test', '14/05/2013']], $rows);
    }

    public function test_legacy_template_is_accepted_without_its_username_column(): void
    {
        $rows = $this->read([
            ['Name', 'Email', 'Username', 'Date of Birth (YYYY-MM)'],
            ['Student Name', 'student@example.test', 'old_username', '2013-05'],
        ]);
        $this->assertSame([['Student Name', 'student@example.test', '2013-05']], $rows);
    }

    public function test_blank_rows_and_formatted_cells_do_not_count_toward_limit(): void
    {
        $book = new Spreadsheet();
        try {
            $sheet = $book->getActiveSheet();
            $sheet->fromArray([StudentImportReader::HEADER, ['Student Name', 'student@example.test', '2013-05']]);
            $sheet->getStyle('Z1500')->getFont()->setBold(true);
            $sheet->setCellValue('E1200', '');
            $this->assertCount(1, (new StudentImportReader())->read($sheet));
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_date_cells_are_read_as_dates_not_display_text(): void
    {
        $book = new Spreadsheet();
        try {
            $sheet = $book->getActiveSheet();
            $sheet->fromArray([StudentImportReader::HEADER, ['Student Name', 'student@example.test']]);
            // US display order: shown as 4/5/2013, which day-first reading would take as 4 May.
            $sheet->setCellValue('C2', Date::PHPToExcel(new \DateTime('2013-04-05')));
            $sheet->getStyle('C2')->getNumberFormat()->setFormatCode('m/d/yyyy');
            $rows = (new StudentImportReader())->read($sheet);
            $this->assertSame('2013-04-05', $rows[0][2]);
            $this->assertSame('2013-04', StudentImportReader::dob($rows[0][2]));
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_exactly_1000_students_are_allowed(): void
    {
        $row = ['Student Name', 'student@example.test', '2013-05'];
        $this->assertCount(1000, $this->read(array_merge([StudentImportReader::HEADER], array_fill(0, 1000, $row))));
    }

    public function test_more_than_1000_students_has_a_specific_limit_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A maximum of 1000 student rows');
        $row = ['Student Name', 'student@example.test', '2013-05'];
        $this->read(array_merge([StudentImportReader::HEADER], array_fill(0, 1001, $row)));
    }

    public function test_wrong_headers_have_a_specific_template_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid student template headers. Expected:');
        $this->read([['Name', 'Date of Birth', 'Email']]);
    }

    public function test_extra_populated_columns_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row 3: Unexpected data');
        $this->read([StudentImportReader::HEADER, [null, null, null], ['Student Name', 'student@example.test', '2013-05', 'unexpected']]);
    }

    public function test_blank_rows_preserve_original_row_numbers(): void
    {
        $rows = $this->read([StudentImportReader::HEADER, [null, null, null], ['Student Name']]);
        $this->assertSame([1], array_keys($rows));
        $this->assertSame(['Student Name', null, null], $rows[1]);
    }

    public static function dobs(): array
    {
        return [
            ['2013-05', '2013-05'], ['2013-5', '2013-05'], ['2013/05', '2013-05'],
            ['2013-05-14', '2013-05'], ['2013/05/14', '2013-05'], ['2013.05.14', '2013-05'],
            ['2013-05-14 00:00:00', '2013-05'],
            ['14/05/2013', '2013-05'], ['14-05-2013', '2013-05'], ['14.05.2013', '2013-05'],
            // Day first unless only the second number can be a day.
            ['05/04/2013', '2013-04'], ['05/14/2013', '2013-05'], ['14/05/13', '2013-05'],
            ['05/2013', '2013-05'], ['5-2013', '2013-05'],
            ['May 2013', '2013-05'], ['May-13', '2013-05'], ['14 May 2013', '2013-05'],
            ['14-May-2013', '2013-05'], ['May 14, 2013', '2013-05'], ['september 2013', '2013-09'],
            ['41408', '2013-05'],
            [' 2013-05 ', '2013-05'],
        ];
    }

    #[DataProvider('dobs')]
    public function test_common_date_of_birth_formats_are_accepted(string $value, string $expected): void
    {
        $this->assertSame($expected, StudentImportReader::dob($value));
    }

    public static function badDobs(): array
    {
        return [[''], ['2013'], ['2013-13'], ['31/02/2013'], ['13/13/2013'], ['13/2013'], ['abc'], ['Foo 2013'], ['next friday']];
    }

    #[DataProvider('badDobs')]
    public function test_unrecognised_dates_of_birth_are_rejected(string $value): void
    {
        $this->assertNull(StudentImportReader::dob($value));
    }
}
