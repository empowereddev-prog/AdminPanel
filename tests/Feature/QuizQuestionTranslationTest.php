<?php

namespace Tests\Feature;

use App\Http\Controllers\QuizController;
use App\Models\QuizQuestion;
use App\Services\QuizQuestionTranslator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Tests\TestCase;

class QuizQuestionTranslationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'quiz_translation_test',
            'database.connections.quiz_translation_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array',
        ]);

        Schema::create('quiz_categories', function (Blueprint $table) {
            $table->id();
            $table->string('color')->nullable();
            $table->string('title_color')->nullable();
            $table->softDeletes();
        });
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->unsignedBigInteger('quiz_id');
            $table->text('question');
            $table->text('question_chinese')->nullable();
            $table->integer('marks');
            $table->string('status');
            foreach (['age', 'image', 'color', 'title_color'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('quiz_categories')->insert(['id' => 1]);
    }

    private function mockTranslator(): \Mockery\MockInterface
    {
        $translator = Mockery::mock(GoogleTranslate::class);
        $translator->shouldReceive('setSource')->with('en')->andReturnSelf();
        $translator->shouldReceive('setTarget')->with('zh')->andReturnSelf();
        $translator->shouldReceive('setOptions')->with(['connect_timeout' => 2, 'timeout' => 5])->andReturnSelf();
        $this->app->instance(GoogleTranslate::class, $translator);

        return $translator;
    }

    public function test_create_saves_english_and_null_chinese_when_google_rate_limits(): void
    {
        $this->mockTranslator()->shouldReceive('translate')->once()->andThrow(new RateLimitException('429 Too Many Requests'));
        Log::spy();

        $response = QuizQuestion::withoutEvents(fn () => app(QuizController::class)->storeEmpathyQuestion(
            Request::create('/', 'POST', ['category' => 1, 'quiz_id' => 2, 'question' => 'How does your friend feel?', 'marks' => 5]),
            1,
            2
        ));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('quiz_questions', ['question' => 'How does your friend feel?', 'question_chinese' => null, 'marks' => 5]);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $context['exception_class'] === RateLimitException::class);
    }

    public function test_update_saves_edited_english_and_clears_stale_translation_on_failure(): void
    {
        DB::table('quiz_questions')->insert([
            'id' => 1, 'quiz_category_id' => 1, 'quiz_id' => 2,
            'question' => 'Old question', 'question_chinese' => '旧问题', 'marks' => 1, 'status' => 'inactive',
        ]);
        $this->mockTranslator()->shouldReceive('translate')->once()->andThrow(new RateLimitException('429 Too Many Requests'));
        Log::spy();

        $response = QuizQuestion::withoutEvents(fn () => app(QuizController::class)->updateEmpathyQuestion(
            Request::create('/', 'PUT', ['category' => 1, 'quiz_id' => 2, 'question' => 'How can you help your friend?', 'marks' => 7, 'status' => 'active']),
            1
        ));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('quiz_questions', ['id' => 1, 'question' => 'How can you help your friend?', 'question_chinese' => null, 'marks' => 7, 'status' => 'active']);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_successful_translations_are_cached_by_exact_english_text(): void
    {
        $this->mockTranslator()->shouldReceive('translate')->once()->with('How do you feel?')->andReturn('你感觉如何？');

        $this->assertSame('你感觉如何？', app(QuizQuestionTranslator::class)->translate('How do you feel?'));
        $this->assertSame('你感觉如何？', app(QuizQuestionTranslator::class)->translate('How do you feel?'));
    }

    public function test_failure_is_not_cached_and_a_later_attempt_can_succeed(): void
    {
        $translator = $this->mockTranslator();
        $translator->shouldReceive('translate')->once()->ordered()->andThrow(new \RuntimeException('Connection timed out'));
        $translator->shouldReceive('translate')->once()->ordered()->andReturn('你感觉如何？');
        Log::spy();

        $this->assertNull(app(QuizQuestionTranslator::class)->translate('How do you feel?'));
        $this->assertSame('你感觉如何？', app(QuizQuestionTranslator::class)->translate('How do you feel?'));
        Log::shouldHaveReceived('warning')->once();
    }

    /** @dataProvider emptyTranslations */
    public function test_empty_provider_responses_return_null_and_are_logged(?string $translation): void
    {
        $this->mockTranslator()->shouldReceive('translate')->once()->andReturn($translation);
        Log::spy();

        $this->assertNull(app(QuizQuestionTranslator::class)->translate('How do you feel?'));
        Log::shouldHaveReceived('warning')->once();
    }

    public static function emptyTranslations(): array
    {
        return [[null], [''], ['   ']];
    }
}
