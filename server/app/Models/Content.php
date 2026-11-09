<?php

namespace App\Models;

class Content extends Document
{
    protected $table = 'content';

    protected $casts = ['payload' => 'array', 'publishedAt' => 'datetime'];
}
