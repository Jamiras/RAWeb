<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class MessageThread extends BaseModel
{
    protected $table = 'message_threads';

    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
