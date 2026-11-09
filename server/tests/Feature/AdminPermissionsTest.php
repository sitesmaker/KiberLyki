<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role): User
    {
        return User::create(['name' => $name, 'username' => $name, 'email' => $name.'@example.test', 'password' => 'test-password', 'role_type' => $role]);
    }

    private function tournament(): Tournament
    {
        $discipline = Discipline::create(['name' => 'Dota 2', 'slug' => 'dota-2']);
        return Tournament::create(['title' => 'Кубок', 'slug' => 'cup', 'discipline_id' => $discipline->id, 'publishedAt' => now()]);
    }

    public function test_content_manager_can_login_edit_content_and_seo_but_not_competitions_or_accounts(): void
    {
        $editor = $this->user('editor', 'content_manager');
        $player = $this->user('player', 'authenticated');
        $tournament = $this->tournament();
        $this->post('/admin/login', ['email' => $editor->email, 'password' => 'test-password'])->assertRedirect('/admin');
        foreach (['/admin', '/admin/content/articles', '/admin/content/articles/new', '/admin/content/first-screen', '/admin/content/about', '/admin/content/staffs', '/admin/content/staffs/new', '/admin/content/setting', '/admin/seo'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin')->assertDontSee('href="/admin/teams"', false)->assertDontSee('href="/admin/users"', false);
        foreach (['/admin/teams', '/admin/content/tournaments/new', '/admin/content/disciplines', '/admin/users', '/admin/tournaments/'.$tournament->documentId.'/export'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/content/articles', ['title' => 'Новость', 'slug' => 'news', 'published' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/admin/seo', ['target' => 'page:home', 'title' => 'SEO клуба', 'indexing' => 'index'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/admin/users/'.$player->id.'/access', ['blocked' => 1])->assertForbidden();
        $this->post('/admin/users/'.$player->id.'/role', ['role_type' => 'admin'])->assertForbidden();
        $this->postJson('/api/tournaments/'.$tournament->documentId.'/generate-bracket')->assertForbidden();
        $this->getJson('/api/tournaments/'.$tournament->documentId.'/registrations')->assertForbidden();
    }

    public function test_manager_can_manage_competitions_and_player_access_but_not_content_or_administrative_roles(): void
    {
        $manager = $this->user('manager', 'manager');
        $player = $this->user('player', 'authenticated');
        $admin = $this->user('admin', 'admin');
        $player->createToken('test');
        $this->actingAs($manager);
        foreach (['/admin', '/admin/teams', '/admin/content/tournaments', '/admin/content/disciplines', '/admin/users'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/seo', '/admin/content/articles', '/admin/content/staffs', '/admin/users?group=administration'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/content/articles', ['title' => 'Нельзя', 'slug' => 'denied'])->assertForbidden();
        $this->post('/admin/seo', ['target' => 'page:home', 'indexing' => 'index'])->assertForbidden();
        $this->post('/admin/users/'.$player->id.'/role', ['role_type' => 'admin'])->assertForbidden();
        $this->post('/admin/users/administration', ['user_id' => $player->id, 'role_type' => 'manager'])->assertForbidden();
        $this->post('/admin/users/'.$admin->id.'/access', ['blocked' => 1])->assertForbidden();
        $this->post('/admin/users/'.$player->id.'/access', ['blocked' => 1])->assertRedirect();
        $this->assertTrue($player->fresh()->blocked);
        $this->assertSame(0, $player->tokens()->count());
        $this->post('/admin/users/'.$player->id.'/access', ['blocked' => 0])->assertRedirect();
        $this->assertFalse($player->fresh()->blocked);
        $this->assertSame('authenticated', $player->fresh()->role_type);
        $tournament = $this->tournament();
        $this->getJson('/api/tournaments/'.$tournament->documentId.'/registrations')->assertOk();
        $this->postJson('/api/tournaments/'.$tournament->documentId.'/generate-bracket')->assertStatus(422);
    }

    public function test_only_admin_can_assign_staff_roles_and_registration_cannot_escalate_privileges(): void
    {
        $admin = $this->user('admin', 'admin');
        $player = $this->user('player', 'authenticated');
        $blocked = $this->user('blocked-player', 'authenticated');
        $blocked->update(['blocked' => true]);
        $staff = $this->user('staff', 'manager');
        $otherAdmin = $this->user('other-admin', 'admin');
        $editor = $this->user('editor', 'content_manager');
        $this->actingAs($admin)->get('/admin/users?q=no-matches')->assertSee('name="user_id"', false)->assertSee($staff->email)->assertSee($otherAdmin->email)->assertSee($editor->email)->assertDontSee($player->email)->assertDontSee($blocked->email);
        $this->post('/admin/users/administration', ['user_id' => $player->id, 'role_type' => 'manager'])->assertSessionHasErrors('user_id');
        $this->post('/admin/users/administration', ['user_id' => $blocked->id, 'role_type' => 'manager'])->assertSessionHasErrors('user_id');
        $this->post('/admin/users/administration', ['user_id' => $admin->id, 'role_type' => 'manager'])->assertSessionHasErrors('user_id');
        $this->actingAs($admin)->post('/admin/users/administration', ['user_id' => $staff->id, 'role_type' => 'content_manager'])->assertRedirect('/admin/users');
        $this->assertSame('content_manager', $staff->fresh()->role_type);
        $this->assertSame('authenticated', $player->fresh()->role_type);
        $this->post('/admin/users/'.$staff->id.'/role', ['role_type' => 'manager'])->assertRedirect();
        $this->assertSame('manager', $staff->fresh()->role_type);
        $this->post('/admin/users/'.$admin->id.'/role', ['role_type' => 'authenticated'])->assertRedirect()->assertSessionHasErrors('operation');
        $this->assertSame('admin', $admin->fresh()->role_type);
        $this->postJson('/api/auth/local/register', ['username' => 'newplayer', 'email' => 'newplayer@example.test', 'password' => 'test-password', 'role_type' => 'admin'])->assertCreated()->assertJsonPath('user.role.type', 'authenticated');
        $legacy = $this->user('legacy', 'organizer');
        $this->assertSame('manager', $legacy->role['type']);
        $this->assertFalse($legacy->canManageContent());
    }

    public function test_admin_creates_staff_with_password_and_validates_duplicates_and_confirmation(): void
    {
        $admin = $this->user('admin', 'admin');
        $data = ['username' => 'neweditor', 'email' => 'NewEditor@example.test', 'role_type' => 'content_manager', 'password' => 'newpass8', 'password_confirmation' => 'newpass8'];
        $this->actingAs($admin)->post('/admin/users/create', $data)->assertRedirect('/admin/users')->assertSessionHasNoErrors();
        $this->get('/admin/users')->assertSee('Создать пользователя администрации')->assertSee('Изменить пароль');
        $editor = User::where('username', 'neweditor')->firstOrFail();
        $this->assertSame('neweditor@example.test', $editor->email);
        $this->assertSame('content_manager', $editor->role_type);
        $this->assertNotSame($data['password'], $editor->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($data['password'], $editor->password));
        $this->assertNotNull($editor->profile);
        $this->post('/admin/users/create', $data)->assertSessionHasErrors(['username', 'email']);
        $this->post('/admin/users/create', array_replace($data, ['username' => 'other', 'email' => 'other@example.test', 'password_confirmation' => 'different']))->assertSessionHasErrors('password');
        $this->assertNull(session('_old_input.password'));
        $this->assertNull(session('_old_input.password_confirmation'));
        $this->assertDatabaseMissing('users', ['username' => 'other']);
        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => $editor->email, 'password' => $data['password']])->assertRedirect('/admin');
        $this->get('/admin/seo')->assertOk();
    }

    public function test_password_changes_require_admin_revoke_tokens_and_invalidate_old_web_sessions(): void
    {
        $admin = $this->user('admin', 'admin');
        $editor = $this->user('editor', 'content_manager');
        $oldHash = $editor->password;
        $editor->createToken('old-device');
        $data = ['password' => 'newpass8', 'password_confirmation' => 'newpass8'];
        foreach (['manager', 'content_manager', 'authenticated'] as $role) {
            $actor = $this->user('actor-'.$role, $role);
            $this->actingAs($actor)->post('/admin/users/'.$editor->id.'/password', $data)->assertForbidden();
            $this->post('/admin/users/create', ['username' => 'denied'])->assertForbidden();
            if ($role === 'manager') {
                $this->get('/admin/users')->assertDontSee('Изменить пароль')->assertDontSee('Создать пользователя администрации');
            }
        }
        $this->actingAs($admin)->post('/admin/users/'.$editor->id.'/password', ['password' => 'short77', 'password_confirmation' => 'short77'])->assertSessionHasErrors('password');
        $this->assertSame($oldHash, $editor->fresh()->password);
        $this->post('/admin/users/'.$editor->id.'/password', $data)->assertRedirect();
        $this->assertSame(0, $editor->tokens()->count());
        $this->assertSame('content_manager', $editor->fresh()->role_type);
        $this->actingAs($editor->fresh())->withSession(['password_user_web' => $editor->id, 'password_hash_web' => $oldHash])->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => $editor->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => $editor->email, 'password' => $data['password']])->assertRedirect('/admin');
    }

    public function test_admin_can_change_own_password_and_is_logged_out(): void
    {
        $admin = $this->user('admin', 'admin');
        $this->actingAs($admin)->post('/admin/users/'.$admin->id.'/password', ['password' => 'replacement-123', 'password_confirmation' => 'replacement-123'])->assertRedirect('/admin/login');
        $this->assertGuest();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('replacement-123', $admin->fresh()->password));
    }

    public function test_each_staff_role_can_change_only_own_password_with_current_password(): void
    {
        foreach (['admin', 'content_manager', 'manager'] as $role) {
            $staff = $this->user('self-'.$role, $role);
            $staff->createToken('old-device');
            $this->actingAs($staff)->get('/admin/account')->assertOk()->assertSee($staff->email);
            $data = ['current_password' => 'wrong-password', 'password' => 'newpass8', 'password_confirmation' => 'newpass8'];
            $this->post('/admin/account/password', $data)->assertSessionHasErrors('current_password');
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check('test-password', $staff->fresh()->password));
            $this->assertNull(session('_old_input.current_password'));
            $this->post('/admin/account/password', array_replace($data, ['current_password' => 'test-password']))->assertRedirect('/admin/login');
            $this->assertGuest();
            $this->assertSame(0, $staff->tokens()->count());
            $this->post('/admin/login', ['email' => $staff->email, 'password' => 'newpass8'])->assertRedirect('/admin');
            $this->post('/admin/logout');
        }
        $player = $this->user('ordinary', 'authenticated');
        $this->actingAs($player)->get('/admin/account')->assertForbidden();
    }

    public function test_admin_can_block_unblock_and_delete_staff_without_losing_history(): void
    {
        $admin = $this->user('admin', 'admin');
        $editor = $this->user('editor', 'content_manager');
        $editor->profile()->create(['nickname' => 'Editor']);
        $oldHash = $editor->password;
        $editor->createToken('old-device');
        $manager = $this->user('manager', 'manager');
        foreach ([$editor, $manager] as $actor) {
            $this->actingAs($actor)->post('/admin/users/'.$editor->id.'/staff-access', ['blocked' => 1])->assertForbidden();
            $this->post('/admin/users/'.$editor->id.'/delete', ['confirm_delete' => 1])->assertForbidden();
        }
        $this->actingAs($admin)->post('/admin/users/'.$admin->id.'/staff-access', ['blocked' => 1])->assertSessionHasErrors('operation');
        $this->post('/admin/users/'.$admin->id.'/delete', ['confirm_delete' => 1])->assertSessionHasErrors('operation');
        $this->post('/admin/users/'.$editor->id.'/staff-access', ['blocked' => 1])->assertRedirect();
        $this->assertTrue($editor->fresh()->blocked);
        $this->assertSame($oldHash, $editor->fresh()->password);
        $this->assertSame(0, $editor->tokens()->count());
        $this->actingAs($editor->fresh())->withSession(['password_user_web' => $editor->id, 'password_hash_web' => $oldHash, 'admin_session_token' => ''])->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => $editor->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->post('/admin/users/'.$editor->id.'/staff-access', ['blocked' => 0])->assertRedirect();
        $this->assertFalse($editor->fresh()->blocked);
        $this->post('/admin/users/'.$editor->id.'/delete', [])->assertSessionHasErrors('confirm_delete');
        $this->post('/admin/users/'.$editor->id.'/delete', ['confirm_delete' => 1])->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $editor->id]);
        $this->assertNull(User::find($editor->id));
        $this->assertDatabaseHas('player_profiles', ['user_id' => $editor->id]);
        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => $editor->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
    }
}
