<?php

namespace App\Models;

class TeamMembership extends Document
{
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
