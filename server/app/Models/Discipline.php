<?php

namespace App\Models;

class Discipline extends Document
{
    protected $casts = ['teamSize' => 'integer'];

    public function icon()
    {
        return $this->belongsTo(Media::class, 'icon_id');
    }
}
