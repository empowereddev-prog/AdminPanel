<?php

namespace Tests\Feature;

use App\Http\Controllers\QuizController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizQuestionOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'quiz_options_test',
            'database.connections.quiz_options_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->text('option_text_chinese')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('quizzes')->insert(['id' => 2, 'status' => 'inactive']);
        DB::table('quiz_questions')->insert(['id' => 1, 'quiz_category_id' => 3, 'status' => 'inactive']);
    }

    public function test_add_options_saves_required_category_and_activates_question_without_translation(): void
    {
        $response = Model::withoutEvents(fn () => app(QuizController::class)->storeQuestionOptions(
            Request::create('/', 'POST', [
                'question_id' => 1, 'quiz_id' => 2, 'options' => ['Listen', 'Ignore'],
                'is_correct' => 0, 'description' => 'Listen to your friend.',
            ]),
            3,
            2
        ));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseCount('quiz_question_options', 2);
        $this->assertDatabaseHas('quiz_question_options', [
            'question_id' => 1, 'quiz_category_id' => 3, 'option_text' => 'Listen',
            'option_text_chinese' => null, 'is_correct' => 1, 'description' => 'Listen to your friend.',
        ]);
        $this->assertDatabaseHas('quiz_question_options', ['option_text' => 'Ignore', 'is_correct' => 0, 'description' => null]);
        $this->assertDatabaseHas('quizzes', ['id' => 2, 'status' => 'active']);
        $this->assertDatabaseHas('quiz_questions', ['id' => 1, 'status' => 'active']);
    }

    public function test_edit_options_replaces_old_answers_and_preserves_explanation_without_translation(): void
    {
        DB::table('quiz_question_options')->insert([
            'id' => 1, 'quiz_category_id' => 3, 'question_id' => 1, 'option_text' => 'Old answer',
        ]);

        $response = Model::withoutEvents(fn () => app(QuizController::class)->updateQuestionOption(
            Request::create('/', 'PUT', [
                'question_id' => 1, 'category_id' => 3, 'quiz_id' => 2,
                'options' => ['Ignore', 'Listen', 'Laugh', 'Leave'], 'is_correct' => 1,
                'descriptions' => [1 => 'Listen with empathy.'],
            ]),
            3,
            2
        ));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(4, DB::table('quiz_question_options')->whereNull('deleted_at')->count());
        $this->assertSoftDeleted('quiz_question_options', ['id' => 1]);
        $this->assertDatabaseHas('quiz_question_options', [
            'question_id' => 1, 'quiz_category_id' => 3, 'option_text' => 'Listen',
            'option_text_chinese' => null, 'is_correct' => 1, 'description' => 'Listen with empathy.', 'deleted_at' => null,
        ]);
        $this->assertSame(1, DB::table('quiz_question_options')->whereNull('deleted_at')->where('is_correct', 1)->count());
    }
}
