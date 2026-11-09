<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role = 'authenticated'): User
    {
        return User::create(['name' => $name, 'username' => $name, 'email' => $name.'@example.test', 'password' => 'test-password', 'role_type' => $role]);
    }

    public function test_domain_changes_follow_request_and_ignore_old_database_domain(): void
    {
        config(['app.site_url' => null, 'app.url' => 'http://localhost:8000']);
        Content::create(['kind' => 'seo', 'payload' => ['siteUrl' => 'http://localhost:5173', 'image' => ['url' => '/storage/cover.png']]]);
        $this->getJson('https://first.example/api/seo?path=%2F')->assertOk()->assertJsonPath('data.canonical', 'https://first.example/')->assertJsonPath('data.image', 'https://first.example/storage/cover.png')->assertJsonPath('data.robots', 'index, follow');
        $this->getJson('https://new-domain.example/api/seo?path=%2F')->assertOk()->assertJsonPath('data.canonical', 'https://new-domain.example/')->assertJsonPath('data.image', 'https://new-domain.example/storage/cover.png');
        $this->get('https://new-domain.example/sitemap.xml')->assertOk()->assertSee('https://new-domain.example/articles')->assertDontSee('localhost')->assertDontSee('first.example');
        $this->get('https://new-domain.example/robots.txt')->assertOk()->assertSee('Sitemap: https://new-domain.example/sitemap.xml');
    }

    public function test_players_cannot_access_admin_or_organizer_actions_and_groups_are_separate(): void
    {
        $player = $this->user('player');
        $organizer = $this->user('organizer', 'organizer');
        $admin = $this->user('admin', 'admin');
        $this->actingAs($player);
        foreach (['/admin', '/admin/seo', '/admin/users', '/admin/content/staffs', '/admin/teams'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/content/staffs', ['firstName' => 'Нельзя'])->assertForbidden();
        $this->post('/admin/users/'.$admin->id.'/role', ['role_type' => 'authenticated'])->assertForbidden();
        $this->putJson('/api/player-profile/me', ['data' => ['nickname' => 'Мой профиль']])->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('organizer@example.test')->assertDontSee('player@example.test');
        $this->get('/admin/users?group=players')->assertOk()->assertSee('player@example.test')->assertDontSee('organizer@example.test')->assertDontSee('name="role_type"', false)->assertSee('Только личный кабинет');
        $this->get('/admin/users?group=players&q=player')->assertOk()->assertSee('name="group" value="players"', false)->assertSee('player@example.test');
        $this->actingAs($organizer)->get('/admin/users')->assertOk()->assertSee('player@example.test')->assertDontSee('admin@example.test');
        $this->get('/admin/users?group=administration')->assertForbidden();
    }

    public function test_https_domain_is_resolved_through_a_configured_reverse_proxy_only(): void
    {
        config(['app.site_url' => null, 'app.trusted_proxies' => ['10.10.0.1']]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.1'])->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'public.example', 'X-Forwarded-Port' => '443'])->getJson('http://internal.example/api/seo?path=%2F')->assertOk()->assertJsonPath('data.canonical', 'https://public.example/');
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.2'])->getJson('http://internal.example/api/seo?path=%2F')->assertOk()->assertJsonPath('data.canonical', 'http://internal.example/');
    }

    public function test_multiple_club_people_are_public_content_not_accounts_or_permissions(): void
    {
        $admin = $this->user('admin', 'admin');
        $this->actingAs($admin);
        $this->post('/admin/content/staffs', ['firstName' => 'Анна', 'lastName' => 'Тестовая', 'specialization' => 'Основатель', 'intro' => 'Создаёт клуб.', 'description' => 'История человека.', 'sortOrder' => 2, 'published' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/admin/content/staffs', ['firstName' => 'Иван', 'lastName' => 'Тестовый', 'specialization' => 'Редактор', 'description' => 'Пишет новости.', 'sortOrder' => 0, 'published' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/admin/content/staffs', ['firstName' => 'Черновик', 'lastName' => 'Тестовый', 'specialization' => 'Организатор', 'sortOrder' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('admin', $admin->fresh()->role_type);
        $this->getJson('/api/staffs')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.firstName', 'Иван')->assertJsonPath('data.1.specialization', 'Основатель');
        $person = Content::where('kind', 'staffs')->where('payload->firstName', 'Анна')->firstOrFail();
        $this->getJson('/api/staffs/'.$person->documentId)->assertOk()->assertJsonPath('data.description', 'История человека.');
        $draft = Content::where('kind', 'staffs')->where('payload->firstName', 'Черновик')->firstOrFail();
        $this->getJson('/api/staffs/'.$draft->documentId)->assertNotFound();
        $this->getJson('/api/seo?path=/club/'.$person->documentId)->assertJsonPath('data.status', 200)->assertJsonPath('data.robots', 'noindex, follow');
        $this->get('/admin/content/staffs/'.$person->documentId.'/edit')->assertOk()->assertSee('Роль в карточке')->assertSee('Основатель');
    }
}
