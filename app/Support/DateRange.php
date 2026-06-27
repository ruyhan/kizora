<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Resolves an admin date-range filter from the request — either a named preset
 * (`range=30`) or a custom span (`from`/`to`). Single source of truth used by
 * every time-based admin screen. Also computes the previous comparable window
 * (for %-change KPIs) and the query-string to echo back to the page/exports.
 */
class DateRange
{
    public const PRESETS = ['today', '7', '30', '90', '365', 'this_month', 'last_month', 'this_year', 'all'];

    public function __construct(
        public ?Carbon $start,
        public ?Carbon $end,
        public ?Carbon $prevStart,
        public ?Carbon $prevEnd,
        public string $range,
        public ?string $from,
        public ?string $to,
        public string $label,
    ) {}

    public static function fromRequest(Request $request, string $default = '30'): self
    {
        $range = (string) $request->get('range', '');
        $from  = $request->get('from');
        $to    = $request->get('to');

        // Custom span wins when valid dates are supplied.
        if ($range === 'custom' || $from || $to) {
            $s = self::parse($from);
            $e = self::parse($to);
            if ($s || $e) {
                $start = ($s ?? $e)->copy()->startOfDay();
                $end   = ($e ?? $s)->copy()->endOfDay();
                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                return self::withPrev($start, $end, 'custom', self::fmt($start, $end), $start->toDateString(), $end->toDateString());
            }
        }

        if (! in_array($range, self::PRESETS, true)) {
            $range = $default;
        }

        return self::preset($range);
    }

    private static function preset(string $range): self
    {
        $now = now();

        return match ($range) {
            'today'      => self::withPrev($now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today', 'Today'),
            '7', '30', '90', '365' => self::withPrev($now->copy()->subDays((int) $range)->startOfDay(), $now->copy()->endOfDay(), $range, 'Last ' . $range . ' days'),
            'this_month' => self::withPrev($now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'this_month', 'This month'),
            'last_month' => self::withPrev($now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), 'last_month', 'Last month'),
            'this_year'  => self::withPrev($now->copy()->startOfYear(), $now->copy()->endOfDay(), 'this_year', 'This year'),
            default      => new self(null, null, null, null, 'all', null, null, 'All time'),
        };
    }

    private static function withPrev(Carbon $start, Carbon $end, string $range, string $label, ?string $from = null, ?string $to = null): self
    {
        $len = $start->diffInSeconds($end) + 1;

        return new self(
            $start,
            $end,
            $start->copy()->subSeconds($len),
            $start->copy()->subSecond(),
            $range,
            $from,
            $to,
            $label,
        );
    }

    private static function parse($value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function fmt(Carbon $s, Carbon $e): string
    {
        return $s->format('j M') . ' – ' . $e->format('j M Y');
    }

    /** Constrain a query to this range on the given column (no-op for "all"). */
    public function applyTo($query, string $col = 'created_at')
    {
        return $query
            ->when($this->start, fn ($q) => $q->where($col, '>=', $this->start))
            ->when($this->end, fn ($q) => $q->where($col, '<=', $this->end));
    }

    /** Query-string params to forward to links/exports. */
    public function toQuery(): array
    {
        return $this->range === 'custom'
            ? ['from' => $this->from, 'to' => $this->to]
            : ['range' => $this->range];
    }

    /** Payload for the DateRangeFilter Vue component. */
    public function filters(): array
    {
        return [
            'range' => $this->range,
            'from'  => $this->from,
            'to'    => $this->to,
            'label' => $this->label,
        ];
    }
}
