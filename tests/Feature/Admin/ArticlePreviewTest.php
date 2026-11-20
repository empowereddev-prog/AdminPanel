<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\KnowleadgeSessionController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ArticlePreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('permission_users', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('menu_id');
            $table->string('is_view');
            $table->string('is_modify');
        });
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
        });
        Schema::create('knowledge_sessions', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->string('title');
            $table->text('description');
            $table->string('written_by')->nullable();
            $table->string('banner_image')->nullable();
        });
        Auth::shouldReceive('user')->andReturn((object) ['id' => 7]);
        DB::table('category')->insert(['id' => 1, 'category_name' => 'Wellbeing']);
        DB::table('knowledge_sessions')->insert([
            'id' => 9, 'category_id' => 1, 'title' => '<Sample> Article',
            'description' => '<p style="line-height:2">Hello <strong>world</strong></p><table><tr><td>Cell</td></tr></table>',
            'written_by' => 'Author',
        ]);
    }

    public function test_view_only_staff_can_preview_saved_formatted_content(): void
    {
        $this->permission('yes', 'no');
        $view = (new KnowleadgeSessionController)->show('9');
        $this->assertSame('admin.knowledgeSession.show', $view->name());
        $article = $view->getData()['article'];
        $this->assertSame('Wellbeing', $article->category->category_name);
        $html = view('admin.knowledgeSession.preview-content', compact('article'))->render();
        $this->assertStringContainsString('&lt;Sample&gt; Article', $html);
        $this->assertStringContainsString($article->description, $html);
        $this->assertStringContainsString('Content-Security-Policy', $html);
        $this->assertStringContainsString("default-src 'none'", $html);
        $this->assertStringContainsString('article-content.css', $html);
    }

    public function test_staff_with_modify_permission_can_preview(): void
    {
        $this->permission('no', 'yes');
        $this->assertSame(9, (new KnowleadgeSessionController)->show('9')->getData()['article']->id);
    }

    public function test_preview_rejects_staff_without_article_access(): void
    {
        $this->permission('no', 'no');
        try {
            (new KnowleadgeSessionController)->show('9');
            $this->fail('Article access must be required.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_preview_rejects_missing_permission_record(): void
    {
        $this->expectException(HttpException::class);
        (new KnowleadgeSessionController)->show('9');
    }

    public function test_missing_article_returns_not_found(): void
    {
        $this->permission('yes', 'no');
        $this->expectException(ModelNotFoundException::class);
        (new KnowleadgeSessionController)->show('999');
    }

    private function permission(string $view, string $modify): void
    {
        DB::table('permission_users')->insert([
            'user_id' => 7, 'menu_id' => 18, 'is_view' => $view, 'is_modify' => $modify,
        ]);
    }
}
