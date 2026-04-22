<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwitchEventSubscription extends Model
{
    protected $fillable = [
        'subscription_id',
        'type',
        'version',
        'condition',
        'status',
        'session_id',
    ];

    protected $casts = [
        'condition' => 'array',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('status', 'enabled');
    }
}
