<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Services\BracketService;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function index()
    {
        return ['data' => Tournament::whereNotNull('publishedAt')->with(['discipline', 'cover'])->orderBy('startsAt')->get()];
    }

    public function details(Tournament $tournament)
    {
        abort_unless($tournament->publishedAt, 404);

        return ['data' => $tournament->load(['discipline', 'cover', 'matches.teamA', 'matches.teamB', 'matches.winner', 'registrations' => fn ($q) => $q->where('registrationStatus', 'approved')->with('team')])];
    }

    public function generate(Request $r, Tournament $tournament, BracketService $service)
    {
        abort_unless($r->user()->isOrganizer(), 403, 'Доступно только организатору');

        return ['data' => $service->generate($tournament)];
    }

    public function result(Request $r, Tournament $tournament, TournamentMatch $match, BracketService $service)
    {
        abort_unless($r->user()->isOrganizer(), 403, 'Доступно только организатору');
        $d = $r->validate(['data.scoreA' => 'required|integer|min:0|max:9999', 'data.scoreB' => 'required|integer|min:0|max:9999'])['data'];
        $service->result($tournament, $match, $d['scoreA'], $d['scoreB']);

        return ['data' => $match->fresh()->load('winner')];
    }
}
