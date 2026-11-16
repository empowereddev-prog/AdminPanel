<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;
use UnexpectedValueException;

class QuizQuestionTranslator
{
    public function __construct(private GoogleTranslate $translator)
    {
    }

    public function translate(string $question): ?string
    {
        $questionHash = hash('sha256', $question);

        try {
            // Only successful translations are cached; later saves can retry a failed translation.
            return Cache::remember('quiz-question:en:zh:v1:' . $questionHash, now()->addDays(30), function () use ($question) {
                $translation = $this->translator
                    ->setSource('en')
                    ->setTarget('zh')
                    ->setOptions(['connect_timeout' => 2, 'timeout' => 5])
                    ->translate($question);

                if ($translation === null || trim($translation) === '') {
                    throw new UnexpectedValueException('Translation service returned no text.');
                }

                return $translation;
            });
        } catch (Throwable $exception) {
            // Do not log question text or provider URLs containing that text.
            Log::warning('Quiz question Chinese translation unavailable; saving English question only.', [
                'question_hash' => $questionHash,
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
            ]);

            return null;
        }
    }
}
