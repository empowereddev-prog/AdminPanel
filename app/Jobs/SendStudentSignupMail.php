<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendStudentSignupMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Recipients per job. The worker runs with --timeout=60, below the
     * connection's retry_after of 90; a job that outlives retry_after is handed
     * to a second worker while the first is still sending, and those parents
     * get their password twice. Sequential SMTP is roughly a second a head, so
     * 20 leaves generous headroom - and a failure now costs one chunk, not the
     * whole import.
     */
    public const CHUNK = 20;

    public $tries = 3;

    protected $studentsToMail;

    /**
     * Create a new job instance.
     */
    public function __construct($studentsToMail)
    {
        $this->studentsToMail = $studentsToMail;
    }

    /**
     * Queue one job per chunk. Every dispatch site should use this rather than
     * handing the whole import to a single job.
     */
    public static function dispatchInChunks(array $studentsToMail): int
    {
        if ($studentsToMail === []) {
            return 0;
        }

        $chunks = array_chunk($studentsToMail, self::CHUNK);
        foreach ($chunks as $chunk) {
            self::dispatch($chunk);
        }

        return count($chunks);
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

    /**
     * Nothing reads failed_jobs, so an exhausted job would otherwise drop out
     * of the queue indistinguishable from a successful one - the parents in
     * this chunk simply never get a password and no one finds out.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('Credential emails failed after all retries', [
            'recipients' => count($this->studentsToMail),
            'emails' => array_column($this->studentsToMail, 'email'),
            'school' => $this->studentsToMail[0]['school_name'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
