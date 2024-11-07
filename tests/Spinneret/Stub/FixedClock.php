<?php

namespace Arakne\Tests\Spinneret\Stub;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

final readonly class FixedClock implements ClockInterface
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2024-09-03T18:49:29+02');
    }

    public static function instance(): FixedClock
    {
        static $instance = new self();

        return $instance;
    }

    public static function modify(string $value): DateTimeImmutable
    {
        return self::instance()->now()->modify($value);
    }
}
