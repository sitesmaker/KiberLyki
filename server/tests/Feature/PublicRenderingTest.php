<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Discipline;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('html-test');
        $this->app->usePublicPath(Storage::disk('html-test')->path(''));
        Storage::disk('html-test')->put('site/index.html', '<!doctype html><html lang="ru"><head><title>Old</title><script type="module" src="/site/assets/app.js"></script></head><body><div id="root"></div></body></html>');
        config(['app.site_url' => 'https://cyberluki.example']);
    }

    private function text(string $html): string
    {
        // Only count real HTML content, not the JSON passed to React widgets.
        return html_entity_decode(strip_tags(preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function tournament(string $name, string $game = 'Dota 2'): Tournament
    {
        $discipline = Discipline::firstOrCreate(['name' => $game], ['slug' => str($game)->slug()->toString()]);

        return Tournament::create(['title' => $name, 'slug' => str($name)->slug()->toString(), 'discipline_id' => $discipline->id, 'description' => 'Описание соревнования', 'rules' => 'Правила честной игры', 'phase' => 'registration', 'registrationStartsAt' => now()->subDay(), 'registrationEndsAt' => now()->addDay(), 'startsAt' => now()->addDays(2), 'publishedAt' => now()]);
    }

    public function test_home_content_is_in_html_and_edits_appear_without_rebuilding_react(): void
    {
        $hero = Content::create(['kind' => 'first-screen', 'publishedAt' => now(), 'payload' => ['title' => 'Клуб чемпионов', 'content' => 'Играем вместе', 'media' => ['url' => '/uploads/intro.mp4', 'mime' => 'video/mp4']]]);
        Content::create(['kind' => 'about', 'publishedAt' => now(), 'payload' => ['title' => 'Наша история', 'description' => 'Объединяем игроков города']]);
        Content::create(['kind' => 'staffs', 'publishedAt' => now(), 'payload' => ['firstName' => 'Иван', 'lastName' => 'Петров', 'specialization' => 'Основатель', 'description' => 'Создаёт сообщество']]);
        Content::create(['kind' => 'staffs', 'payload' => ['firstName' => 'Секретный сотрудник', 'lastName' => 'Черновик']]);
        $tournament = $this->tournament('Открытый кубок');
        $response = $this->get('/')->assertOk()->assertSee('data-server-page', false)->assertSee('autoplay loop muted playsinline', false);
        $text = $this->text($response->getContent());
        foreach (['Клуб чемпионов', 'Играем вместе', 'Наша история', 'Объединяем игроков города', 'Иван Петров', 'Создаёт сообщество', 'Открытый кубок'] as $content) {
            $this->assertStringContainsString($content, $text);
        }
        $response->assertDontSee('Секретный сотрудник')->assertSee('/tournaments/'.$tournament->documentId);
        $hero->update(['payload' => ['title' => 'Обновлённая главная', 'content' => 'Новый текст']]);
        $this->assertStringContainsString('Обновлённая главная', $this->text($this->get('/')->getContent()));
    }

    public function test_news_body_images_and_seo_are_rendered_and_drafts_are_closed(): void
    {
        $article = Content::create(['kind' => 'articles', 'publishedAt' => now(), 'payload' => ['slug' => 'club-news', 'title' => 'Новый сезон', 'content' => 'Полный текст новости', 'cover' => ['url' => '/uploads/news.jpg'], 'gallery' => [['url' => '/uploads/gallery.jpg']], 'author' => 'Редакция']]);
        Content::create(['kind' => 'seo-page', 'payload' => ['target' => 'article:'.$article->documentId, 'title' => 'SEO нового сезона', 'description' => 'Описание для поиска']]);
        $response = $this->get('/articles/club-news')->assertOk()->assertSee('<title>SEO нового сезона</title>', false)->assertSee('/uploads/news.jpg')->assertSee('/uploads/gallery.jpg');
        $this->assertStringContainsString('Полный текст новости', $this->text($response->getContent()));
        $this->assertSame(1, substr_count($response->getContent(), '<h1>'));
        $article->update(['payload' => array_replace($article->payload, ['content' => 'Изменённая новость'])]);
        $this->assertStringContainsString('Изменённая новость', $this->text($this->get('/articles/club-news')->getContent()));
        $article->update(['publishedAt' => null]);
        $this->get('/articles/club-news')->assertNotFound()->assertDontSee('Изменённая новость');
        $this->get('/articles/'.$article->documentId)->assertNotFound()->assertSee('Страница не найдена');
    }

    public function test_news_pagination_and_tournament_filters_work_without_javascript(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            Content::create(['kind' => 'articles', 'publishedAt' => now(), 'createdAt' => now()->addSeconds($index), 'payload' => ['slug' => 'news-'.$index, 'title' => 'Новость номер '.$index]]);
        }
        $this->get('/articles')->assertOk()->assertSee('href="/articles?page=2"', false)->assertSee('Новость номер 13');
        $this->get('/articles?page=2')->assertOk()->assertSee('Новость номер 1')->assertDontSee('Новость номер 13')->assertSee('rel="canonical" href="https://cyberluki.example/articles?page=2"', false);
        $this->tournament('Dota cup');
        $this->tournament('Valorant cup', 'Valorant');
        $html = $this->get('/tournaments?game=Valorant')->assertOk()->getContent();
        $this->assertStringContainsString('Valorant cup', $this->text($html));
        $this->assertStringNotContainsString('Dota cup', $this->text($html));
    }

    public function test_tournament_has_rules_approved_teams_and_match_results_in_html(): void
    {
        $tournament = $this->tournament('Финальный кубок');
        $captain = User::create(['name' => 'Captain', 'username' => 'captain', 'email' => 'private@example.test', 'password' => 'password']);
        $team = Team::create(['name' => 'Команда победителей', 'slug' => 'winners', 'captain_id' => $captain->id]);
        TournamentRegistration::create(['tournament_id' => $tournament->id, 'team_id' => $team->id, 'submitted_by_id' => $captain->id, 'submittedAt' => now(), 'registrationStatus' => 'approved']);
        TournamentMatch::create(['tournament_id' => $tournament->id, 'round' => 1, 'position' => 1, 'team_a_id' => $team->id, 'winner_id' => $team->id, 'scoreA' => 2, 'scoreB' => 0, 'matchStatus' => 'finished']);
        $response = $this->get('/tournaments/'.$tournament->documentId)->assertOk()->assertSee('data-island="competition"', false)->assertDontSee($captain->email);
        $text = $this->text($response->getContent());
        foreach (['Финальный кубок', 'Описание соревнования', 'Правила честной игры', 'Команда победителей', 'Финал', 'Завершён', 'Победа'] as $content) {
            $this->assertStringContainsString($content, $text);
        }
        $response->assertSee('<strong>2</strong>', false);
        $tournament->update(['publishedAt' => null]);
        $this->get('/tournaments/'.$tournament->documentId)->assertNotFound()->assertDontSee('Правила честной игры');
    }

    public function test_server_html_escapes_user_content_and_widget_json(): void
    {
        Content::create(['kind' => 'staffs', 'publishedAt' => now(), 'payload' => ['firstName' => '</script><script>alert(1)</script>', 'lastName' => 'Test', 'specialization' => 'Редактор']]);
        Content::create(['kind' => 'articles', 'publishedAt' => now(), 'payload' => ['slug' => 'unsafe', 'title' => 'Безопасная новость', 'content' => '<img src=x onerror=alert(1)>']]);
        $this->get('/')->assertOk()->assertDontSee('</script><script>alert(1)</script>', false)->assertSee('\u003C\/script\u003E', false);
        $this->get('/articles/unsafe')->assertOk()->assertDontSee('<img src=x onerror=alert(1)>', false)->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }
}
