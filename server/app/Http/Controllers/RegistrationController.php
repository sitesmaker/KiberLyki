<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPlayer;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function mine(Request $r)
    {
        return ['data' => TournamentRegistration::where('submitted_by_id', $r->user()->id)->with(['tournament.discipline', 'tournament.cover', 'team.logo'])->orderByDesc('submittedAt')->get()];
    }

    public function forTournament(Request $r, Tournament $tournament)
    {
        abort_unless($r->user()->isOrganizer(), 403, 'Доступно только организатору');

        return ['data' => $tournament->registrations()->with(['team.logo', 'players.player.profile'])->orderBy('submittedAt')->get()];
    }

    public function register(Request $r)
    {
        $d = $r->validate(['data.tournamentId' => 'required|string|exists:tournaments,documentId', 'data.playerIds' => 'required|array|min:1', 'data.playerIds.*' => 'required|integer|distinct|exists:users,id'])['data'];
        $registration = DB::transaction(function () use ($r, $d) {
            $t = Tournament::where('documentId', $d['tournamentId'])->lockForUpdate()->firstOrFail();
            abort_unless($t->publishedAt, 404);
            abort_unless($t->phase === 'registration' && $t->registrationStartsAt && $t->registrationEndsAt && now()->between($t->registrationStartsAt, $t->registrationEndsAt), 422, 'Регистрация закрыта');
            $team = Team::where('captain_id', $r->user()->id)->where('isActive', true)->lockForUpdate()->first();
            abort_unless($team, 422, 'Действие доступно капитану активной команды');
            abort_unless($team->discipline()->where('disciplines.id', $t->discipline_id)->exists(), 422, 'У команды другая дисциплина');
            abort_if($t->registrations()->where('team_id', $team->id)->where('registrationStatus', '!=', 'withdrawn')->exists(), 422, 'Заявка уже подана');
            abort_if($t->registrations()->whereIn('registrationStatus', ['pending', 'approved'])->count() >= $t->maxTeams, 422, 'Лимит команд исчерпан');
            $members = $team->memberships()->where('membershipStatus', 'active')->where('position', '!=', 'coach')->whereIn('player_id', $d['playerIds'])->with('player.profile')->get();
            abort_unless(count($d['playerIds']) === $t->teamSize && $members->count() === $t->teamSize, 422, 'Выберите ровно '.$t->teamSize.' активных игроков');
            $reg = TournamentRegistration::create(['tournament_id' => $t->id, 'team_id' => $team->id, 'submitted_by_id' => $r->user()->id, 'submittedAt' => now()]);
            foreach ($members as $m) {
                RegistrationPlayer::create(['registration_id' => $reg->id, 'player_id' => $m->player_id, 'nicknameSnapshot' => $m->player->profile?->nickname ?? $m->player->username, 'position' => 'main']);
            }

            return $reg;
        });

        return response()->json(['data' => $registration], 201);
    }

    public function withdraw(Request $r, TournamentRegistration $registration)
    {
        DB::transaction(function () use ($r, $registration) {
            $t = Tournament::whereKey($registration->tournament_id)->lockForUpdate()->firstOrFail();
            $registration->refresh();
            abort_unless($registration->submitted_by_id === $r->user()->id, 403);
            abort_unless(in_array($registration->registrationStatus, ['pending', 'approved']) && ! in_array($t->phase, ['active', 'finished', 'cancelled']), 422, 'Заявку уже нельзя отозвать');
            $registration->update(['registrationStatus' => 'withdrawn']);
        });

        return ['data' => $registration];
    }

    public function review(Request $r, TournamentRegistration $registration)
    {
        abort_unless($r->user()->isOrganizer(), 403, 'Доступно только организатору');
        $d = $r->validate(['data.registrationStatus' => 'required|in:approved,rejected', 'data.rejectionReason' => 'nullable|string|max:2000', 'data.seed' => 'nullable|integer|min:1'])['data'];
        DB::transaction(function () use ($registration, $d) {
            $t = Tournament::whereKey($registration->tournament_id)->lockForUpdate()->firstOrFail();
            $registration->refresh();
            abort_if($t->matches()->exists() || in_array($t->phase, ['active', 'finished', 'cancelled']), 422, 'Состав сетки уже зафиксирован');
            abort_unless($registration->registrationStatus === 'pending', 422, 'Решение уже принято');
            $registration->update([
                'registrationStatus' => $d['registrationStatus'],
                'rejectionReason' => $d['registrationStatus'] === 'rejected' ? ($d['rejectionReason'] ?? null) : null,
                'seed' => $d['registrationStatus'] === 'approved' ? ($d['seed'] ?? null) : null,
            ]);
        });

        return ['data' => $registration];
    }
}
