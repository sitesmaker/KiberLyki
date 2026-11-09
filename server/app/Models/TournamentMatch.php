<?php

namespace App\Models;

class TournamentMatch extends Document
{
    protected $table = 'matches';

    protected $casts = ['round' => 'integer', 'position' => 'integer', 'scoreA' => 'integer', 'scoreB' => 'integer'];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function teamA()
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB()
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }

    public function nextMatch()
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }
}
