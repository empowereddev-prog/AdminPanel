<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStudentSignupMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $studentsToMail;

    /**
     * Create a new job instance.
     */
    public function __construct($studentsToMail)
    {
        $this->studentsToMail = $studentsToMail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->studentsToMail as $student) {
            $emailData = [
                'email' => $student['email'],
                'name' => $student['name'],
                'password' => $student['password'],
                'school_code' => $student['school_code']
            ];
            ___mail_sender($student['email'], 'signup_school_user', $emailData, 'english');
        }
    }
}
