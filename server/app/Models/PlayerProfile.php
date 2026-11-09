<?php

namespace App\Models;

class PlayerProfile extends Document
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function avatar()
    {
        return $this->belongsTo(Media::class, 'avatar_id');
    }
}
