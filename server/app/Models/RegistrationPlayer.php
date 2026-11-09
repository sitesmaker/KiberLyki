<?php

namespace App\Models;

class RegistrationPlayer extends Document
{
    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
