<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\Discipline;
use App\Models\Media;
use App\Models\PlayerProfile;
use App\Models\RegistrationPlayer;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;

class ImportStrapi extends Command
{
    protected $signature = 'cyberluki:import-strapi {database : Path to Strapi SQLite database} {--uploads= : Path to Strapi public/uploads}';

    protected $description = 'Перенести данные Strapi в пустую базу Laravel, сохранив documentId и хеши паролей';

    private PDO $source;

    private array $maps = [];

    private array $rows = [];

    public function handle(): int
    {
        $path = realpath($this->argument('database'));
        if (! $path || ! is_file($path)) {
            $this->error('Исходная база не найдена');

            return self::FAILURE;
        }
        if (User::exists() || Content::exists() || Team::exists() || Discipline::exists() || Media::exists() || Tournament::exists()) {
            $this->error('Импорт разрешён только в пустую базу. Используйте отдельную новую базу Laravel.');

            return self::FAILURE;
        }
        // SQLITE_OPEN_READONLY: the source, including Strapi draft/published versions, stays intact.
        $this->source = new PDO('sqlite:file:'.str_replace('\\', '/', $path).'?mode=ro');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $uploads = $this->option('uploads');
        if ($uploads && ! is_dir($uploads)) {
            $this->error('Каталог uploads не найден');

            return self::FAILURE;
        }
        DB::transaction(function () {
            foreach ($this->read('up_users') as $row) {
                $roleId = $this->link('up_users_role_lnk', 'user_id', $row['id'], 'role_id');
                $roles = $this->read('up_roles');
                $role = collect($roles)->firstWhere('id', $roleId)['type'] ?? 'authenticated';
                $u = new User;
                $u->forceFill(['id' => $row['id'], 'name' => $row['username'], 'username' => $row['username'], 'email' => strtolower($row['email']), 'role_type' => $role === 'organizer' ? 'organizer' : 'authenticated', 'blocked' => (bool) $row['blocked']]);
                // Bypass hashed cast: Strapi uses compatible bcrypt hashes ($2a$).
                $hash = preg_replace('/^\$2[ab]\$/', '\$2y\$', $row['password']);
                $u->setRawAttributes(array_merge($u->getAttributes(), ['password' => $hash]), false);
                $u->save();
                $this->maps['up_users'][$row['id']] = $u->id;
            }
            foreach ($this->read('admin_users') as $row) {
                if (empty($row['is_active']) || ! empty($row['blocked']) || empty($row['password'])) {
                    continue;
                }
                if (User::where('email', strtolower($row['email']))->exists()) {
                    $this->warn('Email администратора уже занят аккаунтом игрока; используйте cyberluki:admin для создания отдельного администратора.');

                    continue;
                }
                $u = new User;
                $u->fill(['name' => trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? '')), 'username' => 'admin-'.Str::lower(Str::random(8)), 'email' => strtolower($row['email']), 'role_type' => 'admin']);
                $u->setRawAttributes(array_merge($u->getAttributes(), ['password' => preg_replace('/^\$2[ab]\$/', '\$2y\$', $row['password'])]), false);
                $u->save();
            }
            $this->import('files', Media::class, ['url', 'mime', 'alternative_text']);
            $this->import('disciplines', Discipline::class, ['name', 'slug', 'team_size'], [], ['icon']);
            $this->import('player_profiles', PlayerProfile::class, ['nickname', 'first_name', 'last_name', 'country', 'bio'], [
                'user_id' => ['player_profiles_user_lnk', 'player_profile_id', 'user_id', 'up_users']], ['avatar']);
            $this->import('teams', Team::class, ['name', 'slug', 'description', 'is_active'], [
                'captain_id' => ['teams_captain_lnk', 'team_id', 'user_id', 'up_users']], ['logo']);
            foreach ($this->read('teams_discipline_lnk') as $link) {
                DB::table('discipline_team')->insertOrIgnore(['team_id' => $this->mapped('teams', $link['team_id']), 'discipline_id' => $this->mapped('disciplines', $link['discipline_id'])]);
            }
            $this->import('team_memberships', TeamMembership::class, ['membership_status', 'position', 'joined_at', 'left_at'], [
                'team_id' => ['team_memberships_team_lnk', 'team_membership_id', 'team_id', 'teams'], 'player_id' => ['team_memberships_player_lnk', 'team_membership_id', 'user_id', 'up_users']]);
            $this->import('tournaments', Tournament::class, ['title', 'slug', 'description', 'rules', 'format', 'team_size', 'max_teams', 'phase', 'registration_starts_at', 'registration_ends_at', 'starts_at', 'published_at'], [
                'discipline_id' => ['tournaments_discipline_lnk', 'tournament_id', 'discipline_id', 'disciplines']], ['cover']);
            $this->import('tournament_registrations', TournamentRegistration::class, ['registration_status', 'submitted_at', 'rejection_reason', 'seed'], [
                'tournament_id' => ['tournament_registrations_tournament_lnk', 'tournament_registration_id', 'tournament_id', 'tournaments'],
                'team_id' => ['tournament_registrations_team_lnk', 'tournament_registration_id', 'team_id', 'teams'],
                'submitted_by_id' => ['tournament_registrations_submitted_by_lnk', 'tournament_registration_id', 'user_id', 'up_users']]);
            $this->import('registration_players', RegistrationPlayer::class, ['nickname_snapshot', 'position'], [
                'registration_id' => ['registration_players_registration_lnk', 'registration_player_id', 'tournament_registration_id', 'tournament_registrations'],
                'player_id' => ['registration_players_player_lnk', 'registration_player_id', 'user_id', 'up_users']]);
            $this->import('tournament_matches', TournamentMatch::class, ['round', 'position', 'score_a', 'score_b', 'match_status', 'scheduled_at', 'next_slot'], [
                'tournament_id' => ['tournament_matches_tournament_lnk', 'match_id', 'tournament_id', 'tournaments'],
                'team_a_id' => ['tournament_matches_team_a_lnk', 'match_id', 'team_id', 'teams'],
                'team_b_id' => ['tournament_matches_team_b_lnk', 'match_id', 'team_id', 'teams'],
                'winner_id' => ['tournament_matches_winner_lnk', 'match_id', 'team_id', 'teams']]);
            foreach ($this->read('tournament_matches_next_match_lnk') as $l) {
                TournamentMatch::whereKey($this->mapped('tournament_matches', $l['match_id']))->update(['next_match_id' => $this->mapped('tournament_matches', $l['inv_match_id'])]);
            }
            // Some legacy demo brackets store rounds/positions without nextMatch links.
            foreach (TournamentMatch::whereNull('next_match_id')->get() as $match) {
                $next = TournamentMatch::where('tournament_id', $match->tournament_id)->where('round', $match->round + 1)->where('position', intdiv($match->position - 1, 2) + 1)->first();
                if ($next) {
                    $match->update(['next_match_id' => $next->id, 'nextSlot' => $match->position % 2 ? 'A' : 'B']);
                }
            }
            foreach ($this->read('captain_transfers') as $row) {
                DB::table('captain_transfers')->insert([
                    'team_id' => $this->mapped('teams', $this->link('captain_transfers_team_lnk', 'captain_transfer_id', $row['id'], 'team_id')),
                    'previous_captain_id' => $this->mapped('up_users', $this->link('captain_transfers_previous_captain_lnk', 'captain_transfer_id', $row['id'], 'user_id')),
                    'next_captain_id' => $this->mapped('up_users', $this->link('captain_transfers_next_captain_lnk', 'captain_transfer_id', $row['id'], 'user_id')),
                    'transferredAt' => $this->date($row['transferred_at'])]);
            }
            foreach (['articles' => ['articles', ['title', 'slug', 'description', 'content', 'author'], ['cover', 'gallery']], 'staffs' => ['staffs', ['first_name', 'last_name', 'specialization', 'description', 'birth_date'], ['photo']],
                'abouts' => ['about', ['title', 'description'], ['image']], 'first_screens' => ['first-screen', ['title', 'content'], ['media']], 'settings' => ['setting', ['title', 'description', 'email', 'phone', 'address', 'work_time'], []]] as $table => [$kind,$fields,$media]) {
                foreach ($this->canonical($table) as $row) {
                    $payload = [];
                    foreach ($fields as $f) {
                        $payload[Str::camel($f)] = $row[$f] ?? null;
                    }
                    foreach ($media as $field) {
                        $ids = $this->mediaIds($table, $row['id'], $field);
                        $items = Media::whereIn('id', $ids)->get()->toArray();
                        $payload[$field] = $field === 'gallery' ? $items : ($items[0] ?? null);
                    }
                    Content::create(['documentId' => $row['document_id'], 'kind' => $kind, 'payload' => $payload, 'publishedAt' => $this->date($row['published_at']), 'createdAt' => $this->date($row['created_at']), 'updatedAt' => $this->date($row['updated_at'])]);
                }
            }
        });
        if ($uploads) {
            $destination = public_path('uploads');
            if (! is_dir($destination)) {
                mkdir($destination, 0775, true);
            }
            foreach (Media::all() as $m) {
                if (! str_starts_with($m->url, '/uploads/')) {
                    continue;
                }
                $name = basename($m->url);
                $from = rtrim($uploads, '/\\').DIRECTORY_SEPARATOR.$name;
                if (! is_file($from)) {
                    $this->warn('Отсутствует файл: '.$name);

                    continue;
                }
                if (! is_file($destination.DIRECTORY_SEPARATOR.$name) && ! copy($from, $destination.DIRECTORY_SEPARATOR.$name)) {
                    throw new \RuntimeException('Не удалось скопировать '.$name);
                }
            }
        }
        $this->info('Импорт завершён. Пользователи: '.User::count().', команды: '.Team::count().', турниры: '.Tournament::count().', матчи: '.TournamentMatch::count());
        $this->info('Активные администраторы Strapi перенесены с прежними email и паролями. Дополнительный аккаунт: cyberluki:admin.');

        return self::SUCCESS;
    }

    private function read(string $table): array
    {
        return $this->rows[$table] ??= $this->source->query('SELECT * FROM "'.$table.'"')->fetchAll(PDO::FETCH_ASSOC);
    }

    private function canonical(string $table): array
    {
        $groups = collect($this->read($table))->groupBy('document_id');
        $result = [];
        foreach ($groups as $group) {
            $result[] = $group->sortByDesc(fn ($r) => [! empty($r['published_at']), $r['updated_at'] ?? 0])->first();
        }

        return $result;
    }

    private function link(string $table, string $key, int $id, string $value): ?int
    {
        return collect($this->read($table))->firstWhere($key, $id)[$value] ?? null;
    }

    private function mapped(string $table, ?int $id): ?int
    {
        return $id === null ? null : ($this->maps[$table][$id] ?? throw new \RuntimeException('Отсутствует связь '.$table.'#'.$id));
    }

    private function date($value): ?string
    {
        return $value === null ? null : (is_numeric($value) ? Carbon::createFromTimestampMs($value, 'UTC')->format('Y-m-d H:i:s') : Carbon::parse($value)->utc()->format('Y-m-d H:i:s'));
    }

    private function mediaIds(string $table, int $id, string $field): array
    {
        $uid = match ($table) {
            'files' => '', 'tournament_matches' => 'api::match.match', default => 'api::'.Str::singular(str_replace('_', '-', $table)).'.'.Str::singular(str_replace('_', '-', $table))
        };

        return collect($this->read('files_related_mph'))->where('related_type', $uid)->where('related_id', $id)->where('field', $field)->sortBy('order')->map(fn ($l) => $this->mapped('files', $l['file_id']))->values()->all();
    }

    private function import(string $table, string $class, array $fields, array $links = [], array $media = []): void
    {
        $documents = [];
        foreach ($this->canonical($table) as $row) {
            $data = ['documentId' => $row['document_id'], 'createdAt' => $this->date($row['created_at']), 'updatedAt' => $this->date($row['updated_at'])];
            foreach ($fields as $f) {
                $data[Str::camel($f)] = str_ends_with($f, '_at') ? $this->date($row[$f] ?? null) : ($row[$f] ?? null);
            }
            foreach ($links as $f => [$link,$key,$value,$target]) {
                $data[$f] = $this->mapped($target, $this->link($link, $key, $row['id'], $value));
            }
            foreach ($media as $f) {
                $data[$f.'_id'] = $this->mediaIds($table, $row['id'], $f)[0] ?? null;
            }
            $item = $class::create($data);
            $documents[$row['document_id']] = $item->id;
        }
        foreach ($this->read($table) as $row) {
            $this->maps[$table][$row['id']] = $documents[$row['document_id']];
        }
    }
}
