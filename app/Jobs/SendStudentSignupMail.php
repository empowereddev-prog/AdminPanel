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
            // Every token the signup_school_user template declares has to be
            // present here. ___mail_sender falls back to $data['otp'] for an
            // unsupplied token, so a gap in this array does not render blank -
            // it renders whatever one-time code happens to be in the payload.
            $emailData = [
                'email' => $student['email'],
                'name' => $student['name'],
                'password' => $student['password'],
                'school_code' => $student['school_code'],
                'school_name' => $student['school_name'] ?? '',
                'year' => (string) date('Y'),
            ];
            ___mail_sender($student['email'], 'signup_school_user', $emailData, 'english');
        }
    }
}
