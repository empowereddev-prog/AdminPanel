<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Emails each independent student their own username and temporary password.
 * The payload carries plaintext passwords, so it is encrypted in the jobs table.
 * The passwords are already saved and listed in the school's credentials
 * spreadsheet, so a retry resends the same details and a failed send still
 * leaves the school able to hand the login over.
 */
class SendStudentLoginMail implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Same reasoning as SendStudentSignupMail::CHUNK: stay well inside --timeout=60. */
    public const CHUNK = 20;

    public $tries = 3;

    /** @param array<int,array{email:string,name:string,username:string,password:string,school_name:string}> $students */
    public function __construct(protected array $students)
    {
    }

    public static function dispatchInChunks(array $students): int
    {
        if ($students === []) {
            return 0;
        }

        $chunks = array_chunk($students, self::CHUNK);
        foreach ($chunks as $chunk) {
            self::dispatch($chunk);
        }

        return count($chunks);
    }

    public function handle(): void
    {
        foreach ($this->students as $student) {
            // Every token the template declares must be present; ___mail_sender
            // falls back to $data['otp'] for a missing one.
            $sent = ___mail_sender($student['email'], 'school_student_login', [
                'name' => $student['name'],
                'username' => $student['username'],
                'password' => $student['password'],
                'school_name' => $student['school_name'],
                'year' => (string) date('Y'),
            ], 'english');
            if (!$sent) {
                Log::warning('Student login email failed', ['email' => $student['email'], 'school' => $student['school_name']]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Student login emails failed after all retries', [
            'recipients' => count($this->students),
            'emails' => array_column($this->students, 'email'),
            'school' => $this->students[0]['school_name'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
