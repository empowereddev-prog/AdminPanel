<?php

namespace App\Services\School;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds .xlsx bytes from a plain array of rows. Every cell is written as an
 * explicit string so usernames, dates and passwords are never reinterpreted
 * (leading zeros, "2013-05" as a date, "=" as a formula).
 */
class CredentialsWorkbook
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function build(array $rows): string
    {
        $book = new Spreadsheet();
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $book->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        ob_start();
        try {
            (new Xlsx($book))->save('php://output');
            return ob_get_contents();
        } finally {
            ob_end_clean();
            $book->disconnectWorksheets();
        }
    }
}
