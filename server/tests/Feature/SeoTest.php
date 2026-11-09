<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Discipline;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function settings(array $overrides = []): Content
    {
        config(['app.site_url' => 'https://cyberluki.example']);
        return Content::create(['kind' => 'seo', 'payload' => array_replace(app(SeoService::class)->settings(), ['siteUrl' => 'https://cyberluki.example', 'indexing' => 'index'], $overrides)]);
    }

    private function organizer(string $role = 'content_manager'): User
    {
        return User::create(['name' => 'user', 'username' => 'user', 'email' => 'user@example.test', 'password' => 'test-password', 'role_type' => $role]);
    }

    public function test_seo_admin_settings_navigation_validation_and_save(): void
    {
        $this->get('/admin/seo')->assertRedirect('/admin/login');
        $user = $this->organizer('authenticated');
        $this->actingAs($user)->get('/admin/seo')->assertForbidden();
        $user->update(['role_type' => 'content_manager']);
        config(['app.site_url' => 'https://cyberluki.example']);
        $this->get('/admin/seo')->assertOk()->assertSee('Содержимое сайта')->assertSee('Какую страницу настроить?')->assertSee('name="page"', false)->assertDontSee('Главная: заголовок')->assertDontSee('value="page:teams"', false);
        $this->post('/admin/seo/settings', ['siteName' => 'КиберЛуки', 'siteUrl' => 'https://cyberluki.example', 'indexing' => 'index'])->assertRedirect('/admin/seo')->assertSessionHasNoErrors();
        $this->post('/admin/seo', ['target' => 'page:home', 'title' => 'Наш клуб в поиске', 'description' => 'Новое описание', 'ogTitle' => 'Заголовок ссылки', 'ogDescription' => 'Описание ссылки', 'indexing' => 'index'])->assertRedirect('/admin/seo?page=page%3Ahome')->assertSessionHasNoErrors();
        $response = $this->getJson('/api/seo?path=%2F');
        $this->assertSame(200, $response->status(), $response->getContent());
        $response->assertJsonPath('data.title', 'Наш клуб в поиске')->assertJsonPath('data.description', 'Новое описание')->assertJsonPath('data.robots', 'index, follow')->assertJsonPath('data.ogTitle', 'Заголовок ссылки')->assertJsonPath('data.ogDescription', 'Описание ссылки');
        $this->get('/admin/seo?page=page%3Ahome')->assertSee('Наш клуб в поиске');
        $this->post('/admin/seo/settings', ['siteName' => '', 'indexing' => 'index'])->assertSessionHasErrors('siteName');
        $response = $this->get('/admin/content/articles')->assertOk();
        $this->assertMatchesRegularExpression('/<details class="admin-content-menu"\s+open\s*>/', $response->getContent());
        $this->get('/admin/login')->assertSee('noindex, nofollow');
    }

    public function test_metadata_is_specific_to_public_records_and_does_not_leak_drafts(): void
    {
        $this->settings();
        $article = Content::create(['kind' => 'articles', 'publishedAt' => now(), 'payload' => ['title' => 'Новость', 'slug' => 'news', 'seoTitle' => 'SEO новости', 'seoDescription' => 'Описание новости', 'cover' => ['url' => '/uploads/news.png']]]);
        $this->getJson('/api/seo?path=/articles/'.$article->documentId)->assertJsonPath('data.title', 'SEO новости')->assertJsonPath('data.canonical', 'https://cyberluki.example/articles/news')->assertJsonPath('data.type', 'article')->assertJsonPath('data.structuredData.headline', 'Новость');
        $this->getJson('/api/seo?path=/articles/news')->assertJsonPath('data.description', 'Описание новости')->assertJsonPath('data.status', 200);
        $draft = Content::create(['kind' => 'articles', 'payload' => ['title' => 'Секретный черновик', 'slug' => 'secret']]);
        $this->getJson('/api/seo?path=/articles/'.$draft->documentId)->assertJsonPath('data.status', 404)->assertJsonPath('data.robots', 'noindex, follow')->assertDontSee('Секретный черновик');
        $discipline = Discipline::create(['name' => 'Dota 2', 'slug' => 'dota-2']);
        $tournament = Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'publishedAt' => now(), 'seoTitle' => 'Кубок — регистрация']);
        $this->getJson('/api/seo?path=/tournaments/'.$tournament->documentId)->assertJsonPath('data.title', 'Кубок — регистрация');
        $team = Team::create(['name' => 'Aurora', 'slug' => 'aurora', 'captain_id' => $this->organizer()->id]);
        $this->getJson('/api/seo?path=/teams/'.$team->documentId)->assertJsonPath('data.status', 200)->assertJsonPath('data.robots', 'noindex, follow');
        $this->getJson('/api/seo?path=/teams')->assertJsonPath('data.status', 200)->assertJsonPath('data.robots', 'noindex, follow');
        $team->update(['isActive' => false]);
        $this->getJson('/api/seo?path=/teams/'.$team->documentId)->assertJsonPath('data.status', 404);
        $this->getJson('/api/seo?path=/cabinet/profile')->assertJsonPath('data.robots', 'noindex, follow');
        $this->getJson('/api/seo?path=/tournaments?game=Dota')->assertJsonPath('data.canonical', 'https://cyberluki.example/tournaments');
    }

    public function test_sitemap_and_robots_include_only_indexable_pages_and_follow_switch(): void
    {
        $settings = $this->settings();
        Content::create(['kind' => 'articles', 'publishedAt' => now(), 'payload' => ['title' => 'Новость', 'slug' => 'news']]);
        Content::create(['kind' => 'articles', 'payload' => ['title' => 'Черновик', 'slug' => 'draft']]);
        $discipline = Discipline::create(['name' => 'Dota 2', 'slug' => 'dota-2']);
        $public = Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'publishedAt' => now()]);
        $draft = Tournament::create(['title' => 'Черновик', 'slug' => 'draft', 'discipline_id' => $discipline->id]);
        $team = Team::create(['name' => 'Aurora', 'slug' => 'aurora', 'captain_id' => $this->organizer()->id]);
        $this->get('/sitemap.xml')->assertOk()->assertSee('https://cyberluki.example/articles/news')->assertSee($public->documentId)->assertDontSee($team->documentId)->assertDontSee('/teams')->assertDontSee($draft->documentId)->assertDontSee('/articles/draft')->assertDontSee('/cabinet');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://cyberluki.example/sitemap.xml')->assertSee('Disallow: /admin');
        $settings->update(['payload' => array_replace($settings->payload, ['indexing' => 'noindex'])]);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>', false);
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $settings->update(['payload' => array_replace($settings->payload, ['indexing' => 'index', 'siteUrl' => 'http://localhost:5173'])]);
        config(['app.site_url' => 'http://localhost:5173']);
        $this->getJson('/api/seo?path=%2F')->assertJsonMissingValidationErrors()->assertOk()->assertJsonPath('data.robots', 'noindex, follow');
    }

    public function test_frontend_html_contains_escaped_metadata_before_react_and_returns_real_404(): void
    {
        Storage::fake('seo-test');
        $this->app->usePublicPath(Storage::disk('seo-test')->path(''));
        Storage::disk('seo-test')->put('site/index.html', '<html><head><title>Old title</title><meta name="description" content="old"></head><body><div id="root"></div></body></html>');
        $this->settings(['homeTitle' => 'Клуб <script>alert(1)</script>', 'homeDescription' => 'Описание "клуба"']);
        $response = $this->get('/')->assertOk()->assertSee('rel="canonical"', false)->assertDontSee('Old title')->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(1, substr_count($response->getContent(), '<title>'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
        $this->get('/missing-page')->assertNotFound()->assertSee('noindex, follow');
        $this->get('/api/unknown')->assertNotFound();
    }

    public function test_page_selection_overrides_are_independent_and_og_images_can_be_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->settings();
        $this->actingAs($this->organizer());
        $article = Content::create(['kind' => 'articles', 'publishedAt' => now(), 'payload' => ['title' => 'Новость', 'slug' => 'news']]);
        $discipline = Discipline::create(['name' => 'Dota 2', 'slug' => 'dota-2']);
        $tournament = Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'publishedAt' => now()]);
        $key = 'article:'.$article->documentId;
        $this->get('/admin/seo')->assertSee($key)->assertSee('tournament:'.$tournament->documentId);
        $this->post('/admin/seo', ['target' => $key, 'title' => 'Новый title новости', 'indexing' => 'noindex', 'image' => \Illuminate\Http\UploadedFile::fake()->image('og.png')])->assertSessionHasNoErrors()->assertRedirect();
        $this->getJson('/api/seo?path=/articles/news')->assertJsonPath('data.title', 'Новый title новости')->assertJsonPath('data.ogTitle', 'Новый title новости')->assertJsonPath('data.robots', 'noindex, follow');
        $image = app(SeoService::class)->forPath('/articles/news')['image'];
        $this->assertStringContainsString('/storage/seo/', $image);
        $this->getJson('/api/seo?path=/tournaments/'.$tournament->documentId)->assertJsonPath('data.title', 'Кубок · КиберЛуки');
        $this->get('/sitemap.xml')->assertDontSee('/articles/news')->assertSee($tournament->documentId);
        $this->get('/admin/seo?page='.urlencode($key))->assertSee('Новый title новости')->assertSee('og.png');
        $this->post('/admin/seo', ['target' => $key, 'title' => '', 'indexing' => 'index', 'remove_image' => '1'])->assertSessionHasNoErrors();
        $this->getJson('/api/seo?path=/articles/news')->assertJsonPath('data.title', 'Новость · КиберЛуки')->assertJsonPath('data.image', null)->assertJsonPath('data.robots', 'index, follow');
        $this->post('/admin/seo', ['target' => 'page:teams', 'title' => 'Не разрешено', 'indexing' => 'index'])->assertSessionHasErrors('target');
    }
}
