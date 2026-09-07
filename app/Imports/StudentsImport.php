<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }

    public function model(array $row)
    {
        return new User([
            'school_id' => $this->school_id,
            'name' => $row['name'],
            'email' => $row['email'],
            'country_code' => $row['country_code'],
            'phone_no' => $row['phone_no'],

        ]);
    }
}


?>
