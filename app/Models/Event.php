<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'timestamp_str',
        'event',
        'zone_idx',
        'level',
        'save',
        'filename',
        'forklift_name',
        'confidence',
        'meta',
    ];

    protected $casts = [
        'save' => 'boolean',
        'confidence' => 'decimal:3',
        'meta' => 'array',
    ];

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }
}
