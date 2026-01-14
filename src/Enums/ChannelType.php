<?php

namespace Codinglabs\NotificationSubscriptions\Enums;

use Illuminate\Support\Str;

enum ChannelType: string
{
    case DATABASE = 'database';
    case MAIL = 'mail';
    case BROADCAST = 'broadcast';

    public function config(?string $key = null): mixed
    {
        $config = config("notification-subscriptions.channels.{$this->value}");

        return $key ? ($config[$key] ?? null) : $config;
    }

    public function driver(): string
    {
        return $this->config('driver') ?? $this->value;
    }

    public function isEnabled(): bool
    {
        $enabled = $this->config('enabled') ?? true;

        return is_callable($enabled) ? $enabled() : (bool) $enabled;
    }

    public function defaultOn(): bool
    {
        return (bool) ($this->config('default_on') ?? true);
    }

    public function hasRateLimiting(): bool
    {
        return (bool) ($this->config('rate_limited') ?? false);
    }

    public function label(): string
    {
        return $this->config('label') ?? Str::title($this->value);
    }

    public function toDatabase(): string
    {
        return $this->value;
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->label()])
            ->toArray();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
