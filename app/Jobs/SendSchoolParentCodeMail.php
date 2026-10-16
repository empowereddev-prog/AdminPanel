<?php

namespace App\Jobs;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Emails the school code to a batch of parents. Chunked like
 * SendStudentSignupMail so one slow SMTP call cannot outlive the queue's
 * retry_after and send a batch twice.
 */
class SendSchoolParentCodeMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CHUNK = 20;

    public $tries = 3;

    /**
     * @param  array<int,string>  $emails
     */
    public function __construct(
        protected array $emails,
        protected string $schoolName,
        protected string $schoolCode,
    ) {
    }

    /**
     * @param  array<int,string>  $emails
     */
    public static function dispatchInChunks(array $emails, string $schoolName, string $schoolCode, ?CarbonInterface $sendAt = null): int
    {
        $chunks = array_chunk(array_values($emails), self::CHUNK);
        foreach ($chunks as $chunk) {
            $pending = self::dispatch($chunk, $schoolName, $schoolCode);
            if ($sendAt) {
                $pending->delay($sendAt);
            }
        }

        return count($chunks);
    }

    public function handle(): void
    {
        foreach ($this->emails as $email) {
            ___mail_sender($email, 'school_parent_code', [
                'school_name' => $this->schoolName,
                'school_code' => $this->schoolCode,
                'app_link' => (string) config('deeplink.web_fallback_url', config('app.url')),
                'year' => (string) date('Y'),
            ], 'english');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('School code emails failed after all retries', [
            'school' => $this->schoolName,
            'recipients' => count($this->emails),
            'emails' => $this->emails,
            'error' => $e->getMessage(),
        ]);
    }
}
