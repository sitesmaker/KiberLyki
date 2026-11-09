<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Discipline;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PDO;
use Tests\TestCase;

class StrapiImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_document_links_passwords_publication_and_source(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'strapi-fixture-');
        try {
            $db = new PDO('sqlite:'.$path);
            $tables = [
                'up_users' => 'id,username,email,password,blocked', 'up_roles' => 'id,type', 'up_users_role_lnk' => 'user_id,role_id',
                'admin_users' => 'id,email,password,is_active,blocked,firstname,lastname',
                'files' => 'id,document_id,created_at,updated_at,url,mime,alternative_text',
                'disciplines' => 'id,document_id,created_at,updated_at,published_at,name,slug,team_size',
                'player_profiles' => 'id,document_id,created_at,updated_at', 'player_profiles_user_lnk' => 'player_profile_id,user_id',
                'teams' => 'id,document_id,created_at,updated_at,published_at,name,slug,description,is_active', 'teams_captain_lnk' => 'team_id,user_id', 'teams_discipline_lnk' => 'team_id,discipline_id',
                'team_memberships' => 'id,document_id', 'team_memberships_team_lnk' => 'team_membership_id,team_id', 'team_memberships_player_lnk' => 'team_membership_id,user_id',
                'tournaments' => 'id,document_id', 'tournaments_discipline_lnk' => 'tournament_id,discipline_id',
                'tournament_registrations' => 'id,document_id', 'tournament_registrations_tournament_lnk' => 'tournament_registration_id,tournament_id', 'tournament_registrations_team_lnk' => 'tournament_registration_id,team_id', 'tournament_registrations_submitted_by_lnk' => 'tournament_registration_id,user_id',
                'registration_players' => 'id,document_id', 'registration_players_registration_lnk' => 'registration_player_id,tournament_registration_id', 'registration_players_player_lnk' => 'registration_player_id,user_id',
                'tournament_matches' => 'id,document_id', 'tournament_matches_tournament_lnk' => 'match_id,tournament_id', 'tournament_matches_team_a_lnk' => 'match_id,team_id', 'tournament_matches_team_b_lnk' => 'match_id,team_id', 'tournament_matches_winner_lnk' => 'match_id,team_id', 'tournament_matches_next_match_lnk' => 'match_id,inv_match_id',
                'captain_transfers' => 'id', 'files_related_mph' => 'file_id,related_id,related_type,field,"order"',
                'articles' => 'id,document_id,created_at,updated_at,published_at,title,slug,description,content,author', 'staffs' => 'id,document_id', 'abouts' => 'id,document_id', 'first_screens' => 'id,document_id', 'settings' => 'id,document_id',
            ];
            foreach ($tables as $table => $columns) {
                $db->exec('CREATE TABLE "'.$table.'" ('.implode(',', array_map(fn ($c) => $c.' TEXT', explode(',', $columns))).')');
            }
            $hash = Hash::make('legacy-password');
            $stmt = $db->prepare('INSERT INTO up_users VALUES (1,?,?,?,0)');
            $stmt->execute(['legacy', 'legacy@test.local', str_replace('$2y$', '$2a$', $hash)]);
            $db->exec("INSERT INTO up_roles VALUES(1,'authenticated'); INSERT INTO up_users_role_lnk VALUES(1,1)");
            $stmt = $db->prepare('INSERT INTO admin_users VALUES (1,?,?,1,0,?,?)');
            $stmt->execute(['admin@test.local', $hash, 'Admin', 'Test']);
            $db->exec("INSERT INTO disciplines VALUES (1,'game-doc',1700000000000,1700000000000,NULL,'Черновик','game',5),(2,'game-doc',1700000000000,1700000000000,1700000000000,'Игра','game',5)");
            $db->exec("INSERT INTO teams VALUES (1,'team-doc',1700000000000,1700000000000,1700000000000,'Команда','team',NULL,1); INSERT INTO teams_captain_lnk VALUES (1,1); INSERT INTO teams_discipline_lnk VALUES (1,1)");
            $db->exec("INSERT INTO articles VALUES (1,'news-doc',1700000000000,1700000000000,NULL,'Черновик','news',NULL,NULL,NULL),(2,'news-doc',1700000000000,1700000000000,1700000000000,'Новость','news',NULL,NULL,NULL)");
            $db = null;
            $before = hash_file('sha256', $path);
            $this->artisan('cyberluki:import-strapi', ['database' => $path])->assertSuccessful();
            $this->assertSame($before, hash_file('sha256', $path));
            $this->assertSame(1, Discipline::count());
            $this->assertSame('game-doc', Team::first()->discipline()->first()->documentId);
            $this->assertSame('Игра', Discipline::first()->name);
            $this->assertTrue(Hash::check('legacy-password', User::where('username', 'legacy')->first()->password));
            $this->assertSame('admin', User::where('email', 'admin@test.local')->first()->role_type);
            $this->assertSame(1, Content::count());
            $this->assertSame('Новость', Content::first()->payload['title']);
            $this->assertSame('news-doc', Content::first()->documentId);
            $this->artisan('cyberluki:import-strapi', ['database' => $path])->assertFailed();
        } finally {
            @unlink($path);
        }
    }
}
