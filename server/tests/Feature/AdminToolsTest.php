<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Discipline;
use App\Models\Media;
use App\Models\PlayerProfile;
use App\Models\RegistrationPlayer;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminToolsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role = 'authenticated'): User
    {
        return User::create(['name' => $name, 'username' => $name, 'email' => $name.'@example.test', 'password' => 'test-password', 'role_type' => $role]);
    }

    public function test_export_download_contains_registered_roster_emails_and_matches_and_requires_admin_access(): void
    {
        $organizer = $this->user('organizer', 'organizer');
        $captain = $this->user('captain');
        PlayerProfile::create(['user_id' => $captain->id, 'nickname' => 'Новое имя', 'firstName' => 'Иван']);
        $discipline = Discipline::create(['name' => 'Dota 2', 'slug' => 'dota-2']);
        $team = Team::create(['name' => '=1+1', 'slug' => 'team', 'captain_id' => $captain->id]);
        $tournament = Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'phase' => 'finished']);
        $registration = TournamentRegistration::create(['team_id' => $team->id, 'tournament_id' => $tournament->id, 'submitted_by_id' => $captain->id, 'submittedAt' => now(), 'registrationStatus' => 'approved']);
        RegistrationPlayer::create(['registration_id' => $registration->id, 'player_id' => $captain->id, 'nicknameSnapshot' => 'Имя в заявке', 'position' => 'main']);
        $player = $this->user('player');
        RegistrationPlayer::create(['registration_id' => $registration->id, 'player_id' => $player->id, 'nicknameSnapshot' => 'Второй игрок', 'position' => 'main']);
        $otherTeam = Team::create(['name' => 'Другая команда', 'slug' => 'other-team', 'captain_id' => $player->id]);
        $otherRegistration = TournamentRegistration::create(['team_id' => $otherTeam->id, 'tournament_id' => $tournament->id, 'submitted_by_id' => $player->id, 'submittedAt' => now()]);
        RegistrationPlayer::create(['registration_id' => $otherRegistration->id, 'player_id' => $player->id, 'nicknameSnapshot' => 'Игрок другой команды', 'position' => 'main']);
        $tournament->matches()->create(['round' => 1, 'position' => 1, 'team_a_id' => $team->id, 'winner_id' => $team->id, 'scoreA' => 0, 'matchStatus' => 'finished']);
        $url = '/admin/tournaments/'.$tournament->documentId.'/export';
        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs($captain)->get($url)->assertForbidden();
        $response = $this->actingAs($organizer)->get($url)->assertOk()->assertDownload();
        $path = tempnam(sys_get_temp_dir(), 'export-test-');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            $this->assertSame(['Турнир', 'Команды', 'Составы', 'Матчи'], $book->getSheetNames());
            $players = $book->getSheetByName('Составы');
            $this->assertSame('Имя в заявке', $players->getCell('B2')->getValue());
            $this->assertSame('Иван', $players->getCell('C2')->getValue());
            $this->assertSame('captain@example.test', $players->getCell('G2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $players->getCell('A2')->getDataType());
            $this->assertSame('=1+1', $players->getCell('A2')->getValue());
            $this->assertSame(['Команда', 'Никнейм в заявке', 'Имя', 'Фамилия', 'Позиция', 'Капитан (текущий)', 'Почта', 'Страна'], $players->rangeToArray('A1:H1')[0]);
            $this->assertContains('A2:A3', $players->getMergeCells());
            $this->assertEmpty($players->getCell('A3')->getValue());
            $this->assertSame('Второй игрок', $players->getCell('B3')->getValue());
            $this->assertSame('player@example.test', $players->getCell('G3')->getValue());
            $this->assertSame('Другая команда', $players->getCell('A4')->getValue());
            foreach ($book->getAllSheets() as $sheet) {
                foreach ($sheet->toArray() as $row) {
                    foreach ($row as $value) {
                        $this->assertDoesNotMatchRegularExpression('/^ID\b/i', (string) $value);
                    }
                }
            }
            $this->assertSame(0, $book->getSheetByName('Матчи')->getCell('D2')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_admin_can_replace_remove_images_and_remove_only_selected_gallery_entries(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('editor', 'content_manager'));
        $first = Media::create(['url' => '/uploads/first.png', 'mime' => 'image/png']);
        $second = Media::create(['url' => '/uploads/second.png', 'mime' => 'image/png']);
        $content = Content::create(['kind' => 'articles', 'payload' => ['title' => 'Новость', 'slug' => 'news', 'cover' => $first->toArray(), 'gallery' => [$first->toArray(), $second->toArray()]]]);
        $url = '/admin/content/articles/'.$content->documentId;
        $this->get($url.'/edit')->assertOk()->assertSee('data-media-picker', false)->assertSee('/uploads/first.png');
        $this->post($url, ['title' => 'Новость', 'slug' => 'news', 'remove_cover' => '1', 'remove_gallery' => [$first->id, 9999], 'gallery' => [UploadedFile::fake()->image('new.png')]])->assertRedirect();
        $payload = $content->refresh()->payload;
        $this->assertNull($payload['cover']);
        $this->assertCount(2, $payload['gallery']);
        $this->assertSame($second->id, $payload['gallery'][0]['id']);
        $this->assertSame('new.png', $payload['gallery'][1]['alternativeText']);
        $this->assertDatabaseHas('media', ['id' => $first->id]);
        $this->post($url, ['title' => 'Новость', 'slug' => 'news', 'cover' => UploadedFile::fake()->image('cover.png')])->assertRedirect();
        $this->assertSame('cover.png', $content->refresh()->payload['cover']['alternativeText']);
        $this->assertCount(2, $content->payload['gallery']);
    }

    public function test_search_is_case_insensitive_supports_cyrillic_and_keeps_query_in_pagination(): void
    {
        $this->actingAs($this->user('admin', 'admin'));
        for ($i = 0; $i < 32; $i++) {
            Content::create(['kind' => 'articles', 'payload' => ['title' => 'Кубок города '.$i, 'slug' => 'cup-'.$i]]);
        }
        Content::create(['kind' => 'articles', 'payload' => ['title' => 'Другая новость', 'slug' => 'other']]);
        $response = $this->get('/admin/content/articles?q='.urlencode('КУБОК'))->assertOk()->assertDontSee('Другая новость');
        $response->assertSee('rel="next"', false)->assertSee('aria-current="page"', false)->assertSee('q='.urlencode('КУБОК'), false)->assertDontSee('Next')->assertDontSee('Previous');
        $this->get('/admin/content/articles?q='.urlencode('КУБОК').'&page=2')->assertOk()->assertSee('Кубок города')->assertDontSee('Другая новость');
        $this->get('/admin/content/articles?q=notfound')->assertOk()->assertSee('Ничего не найдено');
        $this->get('/admin/users?q=admin%40example.test')->assertOk()->assertSee('admin@example.test');
        $this->get('/admin/teams?q=missing')->assertOk()->assertSee('Команды не найдены');
    }

    public function test_avatar_removal_and_tournament_cover_removal_are_persisted(): void
    {
        $organizer = $this->user('organizer', 'organizer');
        $media = Media::create(['user_id' => $organizer->id, 'url' => '/uploads/avatar.png', 'mime' => 'image/png']);
        PlayerProfile::create(['user_id' => $organizer->id, 'nickname' => 'Игрок', 'avatar_id' => $media->id]);
        $this->actingAs($organizer)->putJson('/api/player-profile/me', ['data' => ['nickname' => 'Игрок', 'avatar' => null]])->assertOk()->assertJsonPath('data.avatar', null);
        $this->assertNull($organizer->profile()->first()->avatar_id);
        $discipline = Discipline::create(['name' => 'Dota 2', 'slug' => 'dota-2']);
        $tournament = Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'cover_id' => $media->id, 'phase' => 'draft', 'registrationStartsAt' => now(), 'registrationEndsAt' => now()->addDay(), 'startsAt' => now()->addDays(2)]);
        $this->post('/admin/content/tournaments/'.$tournament->documentId, ['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'teamSize' => 5, 'maxTeams' => 16, 'phase' => 'draft', 'registrationStartsAt' => now()->format('Y-m-d H:i'), 'registrationEndsAt' => now()->addDay()->format('Y-m-d H:i'), 'startsAt' => now()->addDays(2)->format('Y-m-d H:i'), 'remove_cover' => '1'])->assertRedirect('/admin/content/tournaments');
        $this->assertNull($tournament->refresh()->cover_id);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }
}
