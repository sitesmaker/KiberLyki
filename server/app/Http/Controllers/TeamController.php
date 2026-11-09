<?php

namespace App\Http\Controllers;

use App\Models\Discipline;
use App\Models\PlayerProfile;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public static function view(Team $team): Team
    {
        $team->load(['captain', 'discipline', 'logo', 'memberships.player.profile.avatar']);
        $team->captain?->makeHidden(['email', 'blocked']);
        foreach ($team->memberships as $member) {
            $member->player?->makeHidden(['email', 'blocked']);
        }

        return $team;
    }

    public function index()
    {
        return ['data' => Team::where('isActive', true)->orderBy('name')->get()->map(fn ($t) => self::view($t))];
    }

    public function details(Team $team)
    {
        abort_unless($team->isActive, 404);

        return ['data' => self::view($team)];
    }

    public function mine(Request $r)
    {
        $team = Team::where('captain_id', $r->user()->id)->where('isActive', true)->first()
            ?? TeamMembership::where('player_id', $r->user()->id)->where('membershipStatus', 'active')->first()?->team;

        return ['data' => $team ? self::view($team) : null, 'meta' => ['isCaptain' => $team?->captain_id === $r->user()->id]];
    }

    public function create(Request $r)
    {
        $d = $r->validate(['data.name' => 'required|string|max:150', 'data.description' => 'nullable|string|max:5000', 'data.disciplines' => 'required|array|min:1', 'data.disciplines.*' => 'required|string|distinct|exists:disciplines,documentId'])['data'];
        $team = DB::transaction(function () use ($r, $d) {
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            abort_if(TeamMembership::where('player_id', $r->user()->id)->where('membershipStatus', 'active')->exists(), 422, 'Вы уже состоите в команде');
            $team = Team::create(['name' => trim($d['name']), 'slug' => Str::slug($d['name']).'-'.Str::lower(Str::random(6)), 'description' => $d['description'] ?? null, 'captain_id' => $r->user()->id]);
            $team->discipline()->sync(Discipline::whereIn('documentId', $d['disciplines'])->pluck('id'));
            TeamMembership::create(['team_id' => $team->id, 'player_id' => $r->user()->id, 'joinedAt' => now()]);

            return $team;
        });

        return response()->json(['data' => self::view($team)], 201);
    }

    public function captain(Request $r, Team $team): void
    {
        abort_unless($team->isActive && $team->captain_id === $r->user()->id, 403, 'Действие доступно только капитану команды');
    }

    public function update(Request $r, Team $team)
    {
        $this->captain($r, $team);
        $d = $r->validate(['data.name' => 'sometimes|required|string|max:150', 'data.description' => 'nullable|string|max:5000', 'data.disciplines' => 'sometimes|required|array|min:1', 'data.disciplines.*' => 'required|string|distinct|exists:disciplines,documentId'])['data'] ?? [];
        DB::transaction(function () use ($r, $team, $d) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $this->captain($r, $team);
            $team->update(collect($d)->only(['name', 'description'])->all());
            if (isset($d['disciplines'])) {
                $team->discipline()->sync(Discipline::whereIn('documentId', $d['disciplines'])->pluck('id'));
            }
        });

        return ['data' => self::view($team->refresh())];
    }

    public function add(Request $r, Team $team)
    {
        $this->captain($r, $team);
        $r->merge(['data' => array_merge($r->input('data', []), ['email' => strtolower(trim((string) $r->input('data.email')))])]);
        $d = $r->validate(['data.username' => 'required|string|max:100|unique:users,username', 'data.email' => 'required|email|max:255|unique:users,email', 'data.password' => 'required|string|min:8|max:255', 'data.nickname' => 'required|string|max:100', 'data.position' => 'required|in:main,substitute,coach'])['data'];
        $member = DB::transaction(function () use ($r, $team, $d) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $this->captain($r, $team);
            $u = User::create(['name' => $d['username'], 'username' => $d['username'], 'email' => strtolower($d['email']), 'password' => $d['password']]);
            PlayerProfile::create(['user_id' => $u->id, 'nickname' => $d['nickname']]);

            return TeamMembership::create(['team_id' => $team->id, 'player_id' => $u->id, 'position' => $d['position'], 'joinedAt' => now()]);
        });

        return response()->json(['data' => $member], 201);
    }

    public function addExisting(Request $r, Team $team)
    {
        $this->captain($r, $team);
        $d = $r->validate(['data.identifier' => 'required|string', 'data.position' => 'required|in:main,substitute,coach'])['data'];
        $member = DB::transaction(function () use ($r, $team, $d) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $this->captain($r, $team);
            $u = User::where(fn ($q) => $q->where('username', $d['identifier'])->orWhere('email', strtolower($d['identifier'])))->where('blocked', false)->lockForUpdate()->firstOrFail();
            abort_if(TeamMembership::where('player_id', $u->id)->where('membershipStatus', 'active')->exists(), 422, 'Игрок уже состоит в активной команде');

            return TeamMembership::updateOrCreate(['team_id' => $team->id, 'player_id' => $u->id], ['membershipStatus' => 'active', 'position' => $d['position'], 'joinedAt' => now(), 'leftAt' => null]);
        });

        return ['data' => $member];
    }

    public function remove(Request $r, Team $team, TeamMembership $membership)
    {
        DB::transaction(function () use ($r, $team, $membership) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $this->captain($r, $team);
            $membership->refresh();
            abort_unless($membership->team_id === $team->id && $membership->membershipStatus === 'active', 404, 'Игрок не найден');
            abort_if($membership->player_id === $team->captain_id, 422, 'Сначала передайте капитанство');
            $membership->update(['membershipStatus' => 'removed', 'leftAt' => now()]);
        });

        return ['data' => $membership];
    }

    public function transfer(Request $r, Team $team)
    {
        $d = $r->validate(['data.playerId' => 'required|integer|exists:users,id'])['data'];
        DB::transaction(function () use ($r, $team, $d) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $this->captain($r, $team);
            abort_if($team->captain_id === $d['playerId'], 422, 'Этот игрок уже капитан');
            abort_unless($team->memberships()->where('player_id', $d['playerId'])->where('membershipStatus', 'active')->exists(), 422, 'Новый капитан должен быть активным игроком команды');
            abort_if(Team::where('captain_id', $d['playerId'])->where('isActive', true)->exists(), 422, 'Игрок уже капитан другой команды');
            DB::table('captain_transfers')->insert(['team_id' => $team->id, 'previous_captain_id' => $team->captain_id, 'next_captain_id' => $d['playerId'], 'transferredAt' => now()]);
            $team->update(['captain_id' => $d['playerId']]);
        });

        return ['data' => ['captain' => $d['playerId']]];
    }
}
