<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class Document extends Model
{
    public static $snakeAttributes = false;

    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->documentId ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'documentId';
    }
}
