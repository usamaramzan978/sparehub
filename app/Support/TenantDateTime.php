<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Throwable;

final class TenantDateTime
{
    public static function now(): CarbonImmutable
    {
        return Date::now(self::timezone())->toImmutable();
    }

    public static function format(
        mixed $value,
        string $format = 'Y-m-d H:i',
        string $fallback = '-'
    ): string {
        $dateTime = self::toTenant($value);

        if (! $dateTime instanceof CarbonImmutable) {
            return $fallback;
        }

        return $dateTime->format($format);
    }

    public static function startOfDay(string $date): ?CarbonImmutable
    {
        try {
            return Date::createFromFormat('Y-m-d', $date, self::timezone())
                ?->startOfDay()
                ?->toImmutable();
        } catch (Throwable) {
            return null;
        }
    }

    public static function endOfDay(string $date): ?CarbonImmutable
    {
        try {
            return Date::createFromFormat('Y-m-d', $date, self::timezone())
                ?->endOfDay()
                ?->toImmutable();
        } catch (Throwable) {
            return null;
        }
    }

    private static function toTenant(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return Date::instance($value)
                ->setTimezone(self::timezone())
                ->toImmutable();
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Date::parse($value)->setTimezone(self::timezone())->toImmutable();
        } catch (Throwable) {
            return null;
        }
    }

    private static function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }
}
