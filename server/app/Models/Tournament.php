<?php

namespace App\Models;

class Tournament extends Document
{
    protected $casts = ['publishedAt' => 'datetime', 'registrationStartsAt' => 'datetime', 'registrationEndsAt' => 'datetime', 'startsAt' => 'datetime', 'teamSize' => 'integer', 'maxTeams' => 'integer'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function cover()
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class)->orderBy('round')->orderBy('position');
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }
}
