<?php

namespace App\Models;

class Team extends Document
{
    protected $casts = ['isActive' => 'boolean'];

    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function discipline()
    {
        return $this->belongsToMany(Discipline::class);
    }

    public function memberships()
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function logo()
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }
}
