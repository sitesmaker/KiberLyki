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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CyberlukiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role = 'authenticated'): User
    {
        return User::create(['name' => $name, 'username' => $name, 'email' => $name.'@test.local', 'password' => 'test-password', 'role_type' => $role]);
    }

    private function discipline(): Discipline
    {
        return Discipline::create(['name' => 'Dota 2', 'slug' => 'dota2', 'teamSize' => 1]);
    }

    private function tournament(Discipline $d): Tournament
    {
        return Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $d->id, 'teamSize' => 1, 'maxTeams' => 16, 'phase' => 'registration', 'publishedAt' => now(), 'registrationStartsAt' => now()->subDay(), 'registrationEndsAt' => now()->addDay(), 'startsAt' => now()->addDays(2)]);
    }

    private function team(User $u, Discipline $d): Team
    {
        Sanctum::actingAs($u);
        $response = $this->postJson('/api/teams/mine', ['data' => ['name' => $u->username.' team', 'disciplines' => [$d->documentId]]])->assertCreated();

        return Team::where('documentId', $response->json('data.documentId'))->firstOrFail();
    }

    public function test_authentication_tokens_password_changes_and_logout(): void
    {
        $this->postJson('/api/auth/local/register', ['username' => 'captain', 'email' => 'captain@test.local', 'password' => 'test-password'])->assertCreated()->assertJsonStructure(['jwt', 'user' => ['id', 'username', 'role']]);
        $login = $this->postJson('/api/auth/local', ['identifier' => 'captain', 'password' => 'test-password'])->assertOk();
        $token = $login->json('jwt');
        $this->withToken($token)->getJson('/api/users/me')->assertOk()->assertJsonMissingPath('password');
        $this->withToken($token)->postJson('/api/auth/change-password', ['currentPassword' => 'wrong', 'password' => 'new-password', 'passwordConfirmation' => 'new-password'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/auth/change-password', ['currentPassword' => 'test-password', 'password' => 'new-password', 'passwordConfirmation' => 'new-password'])->assertOk();
        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check('new-password', User::first()->password));
    }

    public function test_team_membership_captain_permissions_and_history(): void
    {
        $d = $this->discipline();
        $a = $this->user('captain');
        $b = $this->user('player');
        $other = $this->user('other');
        $team = $this->team($a, $d);
        Sanctum::actingAs($other);
        $this->postJson('/api/teams/'.$team->documentId.'/players/existing', ['data' => ['identifier' => $b->username, 'position' => 'main']])->assertForbidden();
        Sanctum::actingAs($a);
        $this->postJson('/api/teams/'.$team->documentId.'/players/existing', ['data' => ['identifier' => $b->email, 'position' => 'main']])->assertOk();
        $this->postJson('/api/teams/'.$team->documentId.'/players/existing', ['data' => ['identifier' => $b->email, 'position' => 'main']])->assertStatus(422);
        $captainMember = $team->memberships()->where('player_id', $a->id)->first();
        $this->deleteJson('/api/teams/'.$team->documentId.'/players/'.$captainMember->documentId)->assertStatus(422);
        $this->postJson('/api/teams/'.$team->documentId.'/transfer-captain', ['data' => ['playerId' => $b->id]])->assertOk();
        $this->assertDatabaseHas('captain_transfers', ['previous_captain_id' => $a->id, 'next_captain_id' => $b->id]);
        $this->deleteJson('/api/teams/'.$team->documentId.'/players/'.$captainMember->documentId)->assertForbidden();
        Sanctum::actingAs($b);
        $this->deleteJson('/api/teams/'.$team->documentId.'/players/'.$captainMember->documentId)->assertOk();
        $this->assertDatabaseHas('team_memberships', ['player_id' => $a->id, 'membershipStatus' => 'removed']);
        $this->assertDatabaseHas('users', ['id' => $a->id]);
        $this->getJson('/api/teams/'.$team->documentId.'/details')->assertOk()->assertJsonMissingPath('data.captain.email')->assertJsonMissingPath('data.memberships.0.player.password');
    }

    public function test_tournament_registration_three_team_bracket_and_final(): void
    {
        $d = $this->discipline();
        $t = $this->tournament($d);
        $organizer = $this->user('organizer', 'organizer');
        $regs = [];
        foreach (['alpha', 'beta', 'gamma'] as $name) {
            $u = $this->user($name);
            $team = $this->team($u, $d);
            $this->postJson('/api/tournament-registrations/register', ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$u->id]]])->assertCreated();
            $this->postJson('/api/tournament-registrations/register', ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$u->id]]])->assertStatus(422);
            $regs[] = TournamentRegistration::where('team_id', $team->id)->firstOrFail();
        }
        $this->assertDatabaseCount('registration_players', 3);
        $this->postJson('/api/tournaments/'.$t->documentId.'/generate-bracket')->assertForbidden();
        Sanctum::actingAs($organizer);
        foreach ($regs as $reg) {
            $this->putJson('/api/tournament-registrations/'.$reg->documentId.'/review', ['data' => ['registrationStatus' => 'approved']])->assertOk();
        }
        $this->postJson('/api/tournaments/'.$t->documentId.'/generate-bracket')->assertOk()->assertJsonPath('data.bracketSize', 4);
        $this->assertDatabaseCount('matches', 3);
        $this->assertSame(1, TournamentMatch::where('round', 1)->where('matchStatus', 'finished')->count());
        $this->postJson('/api/tournaments/'.$t->documentId.'/generate-bracket')->assertStatus(422);
        $semi = TournamentMatch::where('round', 1)->where('matchStatus', 'pending')->firstOrFail();
        $endpoint = '/api/tournaments/'.$t->documentId.'/matches/'.$semi->documentId.'/result';
        $this->putJson($endpoint, ['data' => ['scoreA' => 1, 'scoreB' => 1]])->assertStatus(422);
        $this->putJson($endpoint, ['data' => ['scoreA' => 2, 'scoreB' => 1]])->assertOk();
        $this->putJson($endpoint, ['data' => ['scoreA' => 3, 'scoreB' => 1]])->assertStatus(422);
        $final = TournamentMatch::where('round', 2)->firstOrFail();
        $this->assertNotNull($final->team_a_id);
        $this->assertNotNull($final->team_b_id);
        $this->putJson('/api/tournaments/'.$t->documentId.'/matches/'.$final->documentId.'/result', ['data' => ['scoreA' => 0, 'scoreB' => 2]])->assertOk();
        $this->assertSame('finished', $t->fresh()->phase);
        $this->getJson('/api/tournaments/'.$t->documentId.'/details')->assertOk()->assertJsonCount(3, 'data.matches');
    }

    public function test_registration_rejects_wrong_roster_discipline_capacity_and_closed_dates(): void
    {
        $d = $this->discipline();
        $u = $this->user('captain');
        $team = $this->team($u, $d);
        $t = $this->tournament($d);
        $stranger = $this->user('stranger');
        $endpoint = '/api/tournament-registrations/register';
        $this->postJson($endpoint, ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$stranger->id]]])->assertStatus(422);
        $this->postJson($endpoint, ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$u->id, $u->id]]])->assertStatus(422);
        $team->discipline()->detach();
        $this->postJson($endpoint, ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$u->id]]])->assertStatus(422);
        $team->discipline()->attach($d);
        $t->update(['registrationEndsAt' => now()->subMinute()]);
        $this->postJson($endpoint, ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$u->id]]])->assertStatus(422);
        $t->update(['registrationEndsAt' => now()->addDay(), 'maxTeams' => 0]);
        $this->postJson($endpoint, ['data' => ['tournamentId' => $t->documentId, 'playerIds' => [$u->id]]])->assertStatus(422);
    }

    public function test_admin_pages_roles_content_and_drafts(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $player = $this->user('player');
        $this->actingAs($player)->get('/admin')->assertForbidden();
        $admin = $this->user('admin', 'admin');
        $this->actingAs($admin);
        $d = $this->discipline();
        $team = $this->team($player, $d);
        $t = $this->tournament($d);
        $this->actingAs($admin);
        foreach (['/admin', '/admin/teams', '/admin/teams/'.$team->documentId, '/admin/tournaments/'.$t->documentId, '/admin/content/articles', '/admin/content/tournaments/new', '/admin/content/tournaments/'.$t->documentId.'/edit', '/admin/users'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->post('/admin/content/articles', ['title' => 'Новость', 'slug' => 'news', 'content' => 'Текст', 'published' => 1])->assertRedirect('/admin/content/articles');
        $this->getJson('/api/articles?pagination[pageSize]=6&filters[slug][$eq]=news')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.pagination.total', 1);
        Content::create(['kind' => 'articles', 'payload' => ['title' => 'Черновик']]);
        $this->getJson('/api/articles')->assertJsonCount(1, 'data');
        $org = $this->user('organizer', 'organizer');
        $this->actingAs($org)->post('/admin/users/'.$player->id.'/role', ['role_type' => 'admin'])->assertForbidden();
    }

    public function test_uploads_profiles_and_blocked_accounts(): void
    {
        Storage::fake('public');
        $u = $this->user('player');
        Sanctum::actingAs($u);
        $this->postJson('/api/upload', ['files' => UploadedFile::fake()->create('evil.svg', 1, 'image/svg+xml')])->assertStatus(422);
        $upload = $this->postJson('/api/upload', ['files' => UploadedFile::fake()->image('avatar.png')])->assertOk();
        $id = $upload->json('0.id');
        $this->putJson('/api/player-profile/me', ['data' => ['nickname' => 'Игрок', 'avatar' => $id]])->assertOk()->assertJsonPath('data.avatar.id', $id);
        $other = $this->user('other');
        Sanctum::actingAs($other);
        $this->putJson('/api/player-profile/me',['data' => ['nickname' => 'Другой', 'avatar' => $id]])->assertForbidden();
        $other->update(['blocked' => true]);
        $this->getJson('/api/teams/mine')->assertForbidden();
    }
}
