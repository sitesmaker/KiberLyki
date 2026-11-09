<?php

namespace Database\Seeders;

use App\Models\Discipline;
use App\Models\PlayerProfile;
use App\Models\RegistrationPlayer;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Services\BracketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public const PASSWORD = 'DemoPlay2026!';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Демонстрационные аккаунты разрешены только в local/testing.');
        }

        DB::transaction(function () {
            $password = Hash::make(self::PASSWORD);
            $games = collect([
                ['Dota 2', 'dota-2', 5], ['Counter-Strike 2', 'counter-strike-2', 5],
                ['Valorant', 'valorant', 5], ['League of Legends', 'league-of-legends', 5],
                ['Overwatch 2', 'overwatch-2', 5], ['Rainbow Six Siege', 'rainbow-six-siege', 5],
                ['Rocket League', 'rocket-league', 3], ['Apex Legends', 'apex-legends', 3],
                ['StarCraft II', 'starcraft-ii', 1], ['Street Fighter 6', 'street-fighter-6', 1],
                ['EA Sports FC', 'ea-sports-fc', 2], ['PUBG: Battlegrounds', 'pubg-battlegrounds', 4],
            ])->map(fn ($g) => Discipline::firstOrCreate(['slug' => $g[1]], ['name' => $g[0], 'teamSize' => $g[2]]));

            $this->account('mock.organizer', 'Демо Организатор', $password, 'organizer');
            for ($i = 1; $i <= 8; $i++) {
                $this->account(sprintf('mock.free%02d', $i), 'Свободный игрок '.$i, $password);
            }

            $names = ['Aurora', 'Iron Ravens', 'Red Comets', 'Quantum Foxes', 'Siberian Tigers', 'Digital Knights', 'Solar Flare', 'Arctic Owls',
                'Velikie Luki United', 'Shadow Sharks', 'Emerald Phoenix', 'Crimson Orbit', 'Black Lotus', 'Silver Lynx', 'Thunder Crew', 'Pixel Pirates',
                'Baltic Titans', 'Night Watch', 'Cosmic Hedgehogs', 'Golden Eagles', 'White Falcons', 'Cyber Samurais', 'Frost Giants', 'Neon Cobras',
                'Ural Legends', 'Volcano Squad', 'Blue Horizons', 'Desert Wolves', 'Purple Haze', 'Storm Riders', 'Lunar Cats', 'Infinity Five'];
            $cities = ['Великие Луки', 'Псков', 'Санкт-Петербург', 'Москва', 'Казань', 'Екатеринбург', 'Новосибирск', 'Калининград'];
            $nicks = ['Pulse', 'Echo', 'Ghost', 'Flash', 'Orbit', 'Nova', 'Zen', 'Coach'];
            $teams = collect();
            foreach ($names as $index => $name) {
                $number = sprintf('%02d', $index + 1);
                $captain = $this->account('mock.captain'.$number, $name.' · Pulse', $password);
                $team = Team::firstOrCreate(['documentId' => 'mock-team-'.$number], [
                    'name' => $name, 'slug' => 'mock-team-'.$number, 'captain_id' => $captain->id,
                    'description' => 'Демонстрационная команда из города '.$cities[$index % count($cities)].'. '.[
                        'Сильная командная игра и спокойное принятие решений.', 'Агрессивный стиль, быстрые атаки и неожиданные тактики.',
                        'Молодой состав, который готовится к первым крупным соревнованиям.', 'Опытный капитан, регулярные тренировки и работа над ошибками.',
                    ][$index % 4],
                ]);
                if ($team->wasRecentlyCreated) {
                    $team->discipline()->sync($games->filter(fn ($g, $gi) => $gi % 4 === $index % 4)->pluck('id'));
                    for ($slot = 0; $slot < 8; $slot++) {
                        $player = $slot === 0 ? $captain : $this->account('mock.team'.$number.'.p'.($slot + 1), $name.' · '.$nicks[$slot], $password);
                        TeamMembership::create([
                            'team_id' => $team->id, 'player_id' => $player->id,
                            'position' => $slot < 5 ? 'main' : ($slot < 7 ? 'substitute' : 'coach'),
                            'joinedAt' => now()->subDays(40 + $index),
                        ]);
                    }
                }
                $teams->push($team);
            }

            foreach ($games as $gi => $game) {
                $eligible = $teams->filter(fn ($team, $ti) => $ti % 4 === $gi % 4)->values();
                foreach (['registration', 'check_in', 'active', 'finished', 'draft'] as $phase) {
                    $title = match ($phase) {
                        'registration' => 'Открытый кубок', 'check_in' => 'Квалификация клуба',
                        'active' => 'Премьер-лига', 'finished' => 'Кубок чемпионов', 'draft' => 'Зимний сезон',
                    };
                    $future = in_array($phase, ['registration', 'draft']);
                    $t = Tournament::firstOrCreate(['slug' => 'mock-'.$game->slug.'-'.$phase], [
                        'title' => $game->name.' — '.$title.' [Демо]', 'discipline_id' => $game->id,
                        'description' => 'Тестовый турнир для знакомства с платформой. '.match ($phase) {
                            'registration' => 'Регистрация открыта: есть принятые, ожидающие, отклонённые и отозванные заявки.',
                            'check_in' => 'Шесть команд приняты. Можно рассмотреть оставшиеся заявки и создать сетку с автоматическими проходами.',
                            'active' => 'Сетка создана, часть результатов записана. Продолжите турнир до финала.',
                            'finished' => 'Турнир сыгран полностью: можно посмотреть все результаты и победителя.',
                            'draft' => 'Неопубликованный турнир для проверки редактирования и публикации.',
                        },
                        'rules' => "Формат: single elimination.\nСостав: {$game->teamSize} игроков. Тренер в игровой состав не входит.\nПобедитель матча проходит дальше. Ничьи запрещены.",
                        'teamSize' => $game->teamSize, 'maxTeams' => 16,
                        'phase' => in_array($phase, ['active', 'finished']) ? 'check_in' : $phase,
                        'registrationStartsAt' => now()->subDays($future ? 3 : 20),
                        'registrationEndsAt' => $future ? now()->addDays(30) : now()->subDays($phase === 'finished' ? 10 : 2),
                        'startsAt' => $future ? now()->addDays(32) : ($phase === 'check_in' ? now()->addDay() : now()->subDays($phase === 'finished' ? 7 : 1)),
                        'publishedAt' => $phase === 'draft' ? null : now()->subDays(5),
                    ]);
                    if (! $t->wasRecentlyCreated || $phase === 'draft') {
                        continue;
                    }
                    foreach ($eligible as $ti => $team) {
                        if ($phase === 'registration' && $ti >= 6) {
                            continue;
                        }
                        $status = $phase === 'registration' ? ['approved', 'approved', 'pending', 'pending', 'rejected', 'withdrawn'][$ti]
                            : ($phase === 'check_in' && $ti >= 6 ? 'pending' : 'approved');
                        $reg = TournamentRegistration::create([
                            'tournament_id' => $t->id, 'team_id' => $team->id, 'submitted_by_id' => $team->captain_id,
                            'registrationStatus' => $status, 'submittedAt' => now()->subDays($future ? 1 : 12)->addMinutes($ti),
                            'seed' => $status === 'approved' ? $ti + 1 : null,
                            'rejectionReason' => $status === 'rejected' ? 'Тестовый пример: заявка отклонена организатором.' : null,
                        ]);
                        $members = $team->memberships()->where('membershipStatus', 'active')->where('position', '!=', 'coach')->with('player.profile')->orderBy('id')->take($game->teamSize)->get();
                        foreach ($members as $m) {
                            RegistrationPlayer::create(['registration_id' => $reg->id, 'player_id' => $m->player_id, 'nicknameSnapshot' => $m->player->profile?->nickname ?? $m->player->username, 'position' => 'main']);
                        }
                    }
                    if (in_array($phase, ['active', 'finished'])) {
                        $bracket = app(BracketService::class);
                        $bracket->generate($t);
                        foreach ($t->matches()->get() as $index => $match) {
                            if ($phase === 'active' && $index >= 3) {
                                break;
                            }
                            $bracket->result($t, $match, $index % 2 ? 1 : 2, $index % 2 ? 2 : 0);
                        }
                    }
                }
            }
        });

        $this->command?->info('Демо-данные добавлены. Повторный запуск не создаёт дубликаты и не сбрасывает изменения.');
    }

    private function account(string $username, string $nickname, string $password, string $role = 'authenticated'): User
    {
        $u = User::firstOrCreate(['username' => $username], ['name' => $nickname, 'email' => $username.'@example.test', 'password' => $password, 'role_type' => $role]);
        if ($u->wasRecentlyCreated) {
            PlayerProfile::create(['user_id' => $u->id, 'nickname' => $nickname, 'firstName' => 'Демо', 'lastName' => 'Игрок', 'country' => 'Россия', 'bio' => 'Тестовый профиль. Люблю командную игру, тренируюсь несколько раз в неделю и участвую в клубных турнирах.']);
        }

        return $u;
    }
}
