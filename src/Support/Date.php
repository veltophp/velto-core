<?php

namespace Velto\Core\Support;

use DateTimeImmutable;
use DateInterval;

class Date
{
    protected DateTimeImmutable $datetime;

    public function __construct($time = 'now', $timezone = null)
    {
        $this->datetime = new DateTimeImmutable($time, $timezone);
    }

    public static function now(): self
    {
        return new self('now');
    }

    public static function today(): self
    {
        return new self(date('Y-m-d') . ' 00:00:00');
    }

    public static function parse(string $datetime): self
    {
        return new self($datetime);
    }

    public static function create(int $year, int $month, int $day): self
    {
        return new self(sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day));
    }

    public function addDays(int $days): self
    {
        return new self($this->datetime->add(new DateInterval("P{$days}D")));
    }

    public function subDays(int $days): self
    {
        return new self($this->datetime->sub(new DateInterval("P{$days}D")));
    }

    public function format(string $format): string
    {
        return $this->datetime->format($format);
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->datetime;
    }

    public function diffForHumans(): string
    {
        $now = new DateTimeImmutable();
        $diff = $now->diff($this->datetime);

        if ($diff->invert) {
            if ($diff->y) return "{$diff->y} year" . ($diff->y > 1 ? 's' : '') . " ago";
            if ($diff->m) return "{$diff->m} month" . ($diff->m > 1 ? 's' : '') . " ago";
            if ($diff->d) return "{$diff->d} day" . ($diff->d > 1 ? 's' : '') . " ago";
            if ($diff->h) return "{$diff->h} hour" . ($diff->h > 1 ? 's' : '') . " ago";
            if ($diff->i) return "{$diff->i} minute" . ($diff->i > 1 ? 's' : '') . " ago";
            return "just now";
        } else {
            if ($diff->y) return "in {$diff->y} year" . ($diff->y > 1 ? 's' : '');
            if ($diff->m) return "in {$diff->m} month" . ($diff->m > 1 ? 's' : '');
            if ($diff->d) return "in {$diff->d} day" . ($diff->d > 1 ? 's' : '');
            if ($diff->h) return "in {$diff->h} hour" . ($diff->h > 1 ? 's' : '');
            if ($diff->i) return "in {$diff->i} minute" . ($diff->i > 1 ? 's' : '');
            return "soon";
        }
    }

    public function isPast(): bool
    {
        return $this->datetime < new DateTimeImmutable();
    }

    public function isFuture(): bool
    {
        return $this->datetime > new DateTimeImmutable();
    }

    public function isWeekend(): bool
    {
        $day = (int) $this->datetime->format('w');
        return $day === 0 || $day === 6;
    }

    public function __toString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }


}
