<?php

namespace Codinglabs\NotificationSubscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationSubscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'channels' => 'json',
    ];

    public function getTable(): string
    {
        return config('notification-subscriptions.table', 'notification_subscriptions');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('notification-subscriptions.user_model'));
    }
}
