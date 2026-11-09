<?php

namespace App\Models;

class TournamentRegistration extends Document
{
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function players()
    {
        return $this->hasMany(RegistrationPlayer::class, 'registration_id');
    }
}
