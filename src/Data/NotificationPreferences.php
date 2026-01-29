<?php

namespace Codinglabs\NotificationSubscriptions\Data;

class NotificationPreferences
{
    public function __construct(
        /** @var array<string, array<string, string>> Channel options per notification type */
        public readonly array $types,
        /** @var array<string, string[]> Enabled channels per notification type */
        public readonly array $values,
        /** @var array<string, string[]> Mandatory channel values per notification type */
        public readonly array $mandatory = [],
    ) {}
}
