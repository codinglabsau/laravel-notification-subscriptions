<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Codinglabs\NotificationSubscriptions\Traits\HasNotificationSubscriptions;

class User extends Authenticatable
{
    use HasFactory;
    use HasNotificationSubscriptions;
    use Notifiable;

    protected $guarded = [];

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
