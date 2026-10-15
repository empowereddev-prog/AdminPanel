<?php

namespace Tests\Unit;

use App\Services\School\StudentImportReader;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            ["\xEF\xBB\xBF Name ", 'EMAIL', 'Username ', 'Date of Birth (YYYY-MM)', null, ''],
            ['Student Name', 'student@example.test', 'student_one', '2013-05', null, ''],
        ]);
        $this->assertCount(1, $rows);
        $this->assertCount(4, $rows[0]);
    }

    public function test_blank_rows_and_formatted_cells_do_not_count_toward_limit(): void
    {
        $book = new Spreadsheet();
        try {
            $sheet = $book->getActiveSheet();
            $sheet->fromArray([StudentImportReader::HEADER, ['Student Name', 'student@example.test', 'student_one', '2013-05']]);
            $sheet->getStyle('Z1500')->getFont()->setBold(true);
            $sheet->setCellValue('E1200', '');
            $this->assertCount(1, (new StudentImportReader())->read($sheet));
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_exactly_1000_students_are_allowed(): void
    {
        $row = ['Student Name', 'student@example.test', 'student_one', '2013-05'];
        $this->assertCount(1000, $this->read(array_merge([StudentImportReader::HEADER], array_fill(0, 1000, $row))));
    }

    public function test_more_than_1000_students_has_a_specific_limit_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A maximum of 1000 student rows');
        $row = ['Student Name', 'student@example.test', 'student_one', '2013-05'];
        $this->read(array_merge([StudentImportReader::HEADER], array_fill(0, 1001, $row)));
    }

    public function test_wrong_headers_have_a_specific_template_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid student template headers. Expected:');
        $this->read([['Name', 'Username', 'Email', 'Date of Birth (YYYY-MM)']]);
    }

    public function test_extra_populated_columns_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row 3: Unexpected data');
        $this->read([StudentImportReader::HEADER, [null, null, null, null], ['Student Name', 'student@example.test', 'student_one', '2013-05', 'unexpected']]);
    }

    public function test_blank_rows_preserve_original_row_numbers(): void
    {
        $rows = $this->read([StudentImportReader::HEADER, [null, null, null, null], ['Student Name']]);
        $this->assertSame([1], array_keys($rows));
        $this->assertSame(['Student Name', null, null, null], $rows[1]);
    }
}