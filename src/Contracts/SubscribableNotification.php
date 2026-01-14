<?php

namespace Codinglabs\NotificationSubscriptions\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SubscribableNotification
{
    public static function type(): string;

    public static function channels(): array;

    public static function values(): array;

    public function subject(): ?Model;

    public static function dispatch();

    public function subscribers();
}
