<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Collects the small amount of volatile schedule data used by the Admin Abuse
 * Wiki page. Item facts remain in seo_items; this service only owns schedule
 * metadata under seo_games.settings_json.sab_wiki.admin_abuse.
 */
final class SabWikiAdminAbuseScheduleService
{
    public const TIMEZONE = 'America/New_York';

    public const ADMIN_ABUSE_SOURCE = 'https://stealabrainrot.fandom.com/wiki/Admin_Abuse';

    public const TACO_TUESDAY_SOURCE = 'https://stealabrainrot.fandom.com/wiki/Taco_Tuesday';

    private const ADMIN_ABUSE_API = 'https://stealabrainrot.fandom.com/api.php';

    private const TACO_TUESDAY_API = 'https://stealabrainrot.fandom.com/api.php';

    private const DEFAULT_TTL_HOURS = 12;

    /**
     * Player-facing timezone rows for the next stored occurrence. Labels stay
     * short; IANA IDs do the DST math.
     *
     * @var list<array{id: string, label: string, timezone: string}>
     */
    private const PLAYER_TIMEZONES = [
        ['id' => 'hawaii', 'label' => 'Hawaii', 'timezone' => 'Pacific/Honolulu'],
        ['id' => 'pacific', 'label' => 'Pacific', 'timezone' => 'America/Los_Angeles'],
        ['id' => 'mountain', 'label' => 'Mountain', 'timezone' => 'America/Denver'],
        ['id' => 'central', 'label' => 'Central', 'timezone' => 'America/Chicago'],
        ['id' => 'eastern', 'label' => 'Eastern', 'timezone' => 'America/New_York'],
        ['id' => 'utc', 'label' => 'UTC', 'timezone' => 'UTC'],
        ['id' => 'london', 'label' => 'London', 'timezone' => 'Europe/London'],
        ['id' => 'paris', 'label' => 'Paris', 'timezone' => 'Europe/Paris'],
        ['id' => 'dubai', 'label' => 'Dubai', 'timezone' => 'Asia/Dubai'],
        ['id' => 'india', 'label' => 'India', 'timezone' => 'Asia/Kolkata'],
        ['id' => 'singapore', 'label' => 'Singapore', 'timezone' => 'Asia/Singapore'],
        ['id' => 'japan', 'label' => 'Japan', 'timezone' => 'Asia/Tokyo'],
        ['id' => 'sydney', 'label' => 'Sydney', 'timezone' => 'Australia/Sydney'],
    ];

    /**
     * Return the stored schedule without making a network request.
     *
     * @return array<string, mixed>
     */
    public function stored(SeoGame $game): array
    {
        $stored = data_get($game->settings_json, 'sab_wiki.admin_abuse', []);

        if (! is_array($stored)) {
            return [];
        }
        // Accept the explicit manual namespace used by admin edits while
        // keeping the normalized event keys convenient for the renderer.
        foreach (['admin_abuse', 'taco_tuesday'] as $key) {
            $manual = data_get($stored, 'manual.'.$key)
                ?? data_get($stored, 'manual_confirmed.'.$key)
                ?? data_get($stored, 'confirmed.'.$key);
            if (! is_array($manual)) {
                continue;
            }
            $event = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            $stored[$key] = array_merge($event, $manual, [
                'manual' => true,
                'source' => 'manual',
            ]);
        }

        return $stored;
    }

    /**
     * Refresh schedule metadata. A dry run performs parsing but never saves
     * the game settings. Manual fields always win over collected fields.
     *
     * @return array{changed: bool, dry_run: bool, data: array<string, mixed>, errors: list<string>, sources: array<string, mixed>}
     */
    public function refresh(
        SeoGame $game,
        bool $dryRun = false,
        bool $force = false,
        ?int $timeoutSeconds = null,
    ): array {
        $existing = $this->stored($game);
        $timeoutSeconds = max(5, min(120, $timeoutSeconds ?? 30));
        $errors = [];
        $rawSources = [];
        $parsed = [];

        foreach ([
            'admin_abuse' => [self::ADMIN_ABUSE_API, 'Admin Abuse'],
            'taco_tuesday' => [self::TACO_TUESDAY_API, 'Taco Tuesday'],
        ] as $key => [$endpoint, $page]) {
            try {
                $response = Http::acceptJson()
                    ->retry(2, 500)
                    ->timeout($timeoutSeconds)
                    ->get($endpoint, [
                        'action' => 'parse',
                        'page' => $page,
                        'prop' => 'wikitext|text|categories',
                        'format' => 'json',
                        'formatversion' => 2,
                    ]);
                if (! $response->successful()) {
                    throw new \RuntimeException('HTTP '.$response->status());
                }

                $payload = $response->json();
                $text = $this->extractSourceText($payload);
                if ($text === '') {
                    throw new \RuntimeException('source returned no wiki text');
                }
                $rawSources[$key] = $text;
                $parsed[$key] = $this->parseEvent($text, $key);
            } catch (\Throwable $e) {
                $errors[] = ucfirst(str_replace('_', ' ', $key)).': '.$e->getMessage();
                Log::warning('SAB Wiki schedule source failed', [
                    'topic' => $key,
                    'source' => $key === 'admin_abuse' ? self::ADMIN_ABUSE_SOURCE : self::TACO_TUESDAY_SOURCE,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $checkedAt = CarbonImmutable::now('UTC')->toIso8601String();
        $sourceHash = $rawSources === []
            ? (string) ($existing['source_hash'] ?? '')
            : hash('sha256', (string) json_encode($rawSources, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $next = $this->mergeCollectedData($existing, $parsed, $checkedAt, $sourceHash, $errors, $force, $rawSources !== []);
        $changed = $next !== $existing;

        if (! $dryRun && $changed) {
            $settings = is_array($game->settings_json) ? $game->settings_json : [];
            $settings['sab_wiki']['admin_abuse'] = $next;
            $game->forceFill(['settings_json' => $settings])->save();
        }

        return [
            'changed' => $changed,
            'dry_run' => $dryRun,
            'data' => $next,
            'errors' => $errors,
            'sources' => [
                'admin_abuse' => self::ADMIN_ABUSE_SOURCE,
                'taco_tuesday' => self::TACO_TUESDAY_SOURCE,
                'checked_at' => $checkedAt,
                'hash' => $sourceHash,
            ],
        ];
    }

    /** @return array{changed: bool, dry_run: bool, data: array<string, mixed>, errors: list<string>, sources: array<string, mixed>} */
    public function sync(SeoGame $game, bool $dryRun = false, bool $force = false, ?int $timeoutSeconds = null): array
    {
        return $this->refresh($game, $dryRun, $force, $timeoutSeconds);
    }

    /** @return array{changed: bool, dry_run: bool, data: array<string, mixed>, errors: list<string>, sources: array<string, mixed>} */
    public function preview(SeoGame $game, bool $force = false, ?int $timeoutSeconds = null): array
    {
        return $this->refresh($game, true, $force, $timeoutSeconds);
    }

    /**
     * Build render-safe, current schedule data from stored values. No network
     * request is allowed here, so previews and static renders are deterministic.
     *
     * @return array<string, mixed>
     */
    public function pageData(SeoGame $game, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now(self::TIMEZONE);
        $stored = $this->stored($game);
        $admin = $this->displayEvent($this->applyOverride($stored, 'admin_abuse'), 'Admin Abuse', $now);
        $taco = $this->displayEvent($this->applyOverride($stored, 'taco_tuesday'), 'Taco Tuesday', $now);
        $sourceChecked = $this->parseDate($stored['source_checked_at'] ?? null, 'UTC');
        $sourceStatus = (string) ($stored['status'] ?? 'unconfirmed');
        $sourceStatus = in_array($sourceStatus, ['confirmed', 'active', 'community'], true) ? 'confirmed' : 'unconfirmed';
        if ($sourceStatus === 'unconfirmed'
            && ($this->eventIsManual($stored, 'admin_abuse') || $this->eventIsManual($stored, 'taco_tuesday'))) {
            $sourceStatus = 'confirmed';
        }
        if (in_array(strtolower((string) ($stored['status'] ?? '')), ['cancelled', 'canceled'], true)) {
            $admin = null;
            $taco = null;
            $sourceStatus = 'unconfirmed';
        }
        $mechanics = array_values(array_filter(array_map(
            static fn ($value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null,
            (array) ($stored['mechanics'] ?? []),
        )));

        $events = array_values(array_filter([$admin, $taco], static fn (?array $event): bool => $event !== null));
        $statuses = array_values(array_filter(array_map(static fn (array $event): ?string => $event['status'] ?? null, $events)));
        $overall = $sourceStatus === 'unconfirmed' || $events === []
            ? 'not_confirmed'
            : (in_array('live', $statuses, true) ? 'live' : (in_array('today', $statuses, true) ? 'today' : 'upcoming'));

        return [
            'timezone' => self::TIMEZONE,
            'timezone_label' => $now->format('T'),
            'status' => $overall,
            'status_label' => $this->statusLabel($overall),
            'admin_abuse' => $admin,
            'taco_tuesday' => $taco,
            'events' => $events,
            'mechanics' => $mechanics,
            'source_status' => $sourceStatus,
            'source_checked_at' => $sourceChecked?->toIso8601String(),
            'source_checked_label' => $sourceChecked
                ? $sourceChecked->setTimezone('America/Los_Angeles')->format('M j, Y g:i A T')
                : 'Unknown',
            'source_urls' => [self::ADMIN_ABUSE_SOURCE, self::TACO_TUESDAY_SOURCE],
            'timezone_rows' => $this->timezoneRows($admin, $taco),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $admin
     * @param  array<string, mixed>|null  $taco
     * @return list<array{id: string, label: string, timezone: string, admin_abuse: ?array{datetime: string, label: string}, taco_tuesday: ?array{datetime: string, label: string}}>
     */
    private function timezoneRows(?array $admin, ?array $taco): array
    {
        if ($admin === null && $taco === null) {
            return [];
        }

        return array_map(fn (array $zone): array => [
            'id' => $zone['id'],
            'label' => $zone['label'],
            'timezone' => $zone['timezone'],
            'admin_abuse' => $this->timezoneCell($admin, $zone['timezone']),
            'taco_tuesday' => $this->timezoneCell($taco, $zone['timezone']),
        ], self::PLAYER_TIMEZONES);
    }

    /**
     * @param  array<string, mixed>|null  $event
     * @return array{datetime: string, label: string}|null
     */
    private function timezoneCell(?array $event, string $timezone): ?array
    {
        if (! is_array($event) || empty($event['next_event_at'])) {
            return null;
        }

        try {
            $local = CarbonImmutable::parse((string) $event['next_event_at'])->setTimezone($timezone);
        } catch (\Throwable) {
            return null;
        }

        return [
            'datetime' => $local->toIso8601String(),
            'label' => $local->format('D, M j, Y g:i A T'),
        ];
    }

    /** @return array<string, mixed> */
    private function mergeCollectedData(
        array $existing,
        array $parsed,
        string $checkedAt,
        string $sourceHash,
        array $errors,
        bool $force,
        bool $sourceUpdated,
    ): array {
        $result = $existing;
        foreach (['admin_abuse', 'taco_tuesday'] as $key) {
            $incoming = $parsed[$key] ?? null;
            $old = is_array($existing[$key] ?? null) ? $existing[$key] : [];
            if (is_array($incoming) && $incoming !== []) {
                $result[$key] = $this->mergeEvent($old, $incoming, $this->eventIsManual($existing, $key));
            } elseif ($old === []) {
                $result[$key] = [
                    'status' => 'unconfirmed',
                    'source' => 'not_confirmed',
                ];
            }
        }

        $result['timezone'] = self::TIMEZONE;
        $result['source_url'] = self::ADMIN_ABUSE_SOURCE;
        $result['source_urls'] = [self::ADMIN_ABUSE_SOURCE, self::TACO_TUESDAY_SOURCE];
        if ($sourceUpdated || ! array_key_exists('source_checked_at', $result)) {
            $result['source_checked_at'] = $sourceUpdated ? $checkedAt : null;
        }
        $result['source_hash'] = $sourceHash;
        $result['ttl_hours'] = (int) ($existing['ttl_hours'] ?? self::DEFAULT_TTL_HOURS);
        $result['confidence'] = $this->eventIsManual($existing, 'admin_abuse') || $this->eventIsManual($existing, 'taco_tuesday')
            ? 'manual'
            : ($parsed === [] ? 'unknown' : 'community');
        $result['status'] = $this->hasConfirmedEvent($result) ? 'confirmed' : 'unconfirmed';
        $result['last_error'] = $errors === [] ? null : implode('; ', $errors);
        $result['force_refresh'] = $force;

        $mechanics = [];
        foreach ($parsed as $event) {
            foreach ((array) ($event['mechanics'] ?? []) as $mechanic) {
                if (is_string($mechanic) && ! in_array($mechanic, $mechanics, true)) {
                    $mechanics[] = $mechanic;
                }
            }
        }
        if ($mechanics !== [] && ! (bool) ($existing['manual_override'] ?? false)) {
            $result['mechanics'] = $mechanics;
        }
        unset($result['force_refresh']);

        return $result;
    }

    /** @return array<string, mixed> */
    private function mergeEvent(array $old, array $incoming, bool $manual): array
    {
        if ($manual) {
            return array_merge($incoming, $old, [
                'manual' => true,
                'status' => $old['status'] ?? $incoming['status'] ?? 'confirmed',
            ]);
        }

        return array_merge($old, $incoming);
    }

    private function eventIsManual(array $stored, string $key): bool
    {
        $event = is_array($stored[$key] ?? null) ? $stored[$key] : [];
        $manual = data_get($stored, 'manual_override', false)
            || data_get($stored, 'manual.'.$key, false)
            || data_get($stored, 'manual_confirmed.'.$key, false)
            || data_get($stored, 'confirmed.'.$key, false)
            || data_get($event, 'manual', false)
            || data_get($event, 'is_manual', false)
            || trim((string) data_get($event, 'confirmed_by', '')) !== ''
            || data_get($event, 'source', '') === 'manual';

        return (bool) $manual;
    }

    private function hasConfirmedEvent(array $stored): bool
    {
        foreach (['admin_abuse', 'taco_tuesday'] as $key) {
            $event = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            if (($event['weekday'] ?? null) && ($event['time'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function parseEvent(string $text, string $key): array
    {
        $weekday = $key === 'taco_tuesday' ? 'Tuesday' : null;
        $time = null;
        $pattern = '/(?:every|each)\s+(sunday|monday|tuesday|wednesday|thursday|friday|saturday)[^\.\n]{0,220}?\b(?:at\s*)?(\d{1,2})(?::(\d{2}))?\s*(a\.?m\.?|p\.?m\.?)\b/iu';
        if (preg_match($pattern, $text, $match)) {
            $weekday = ucfirst(strtolower($match[1]));
            $time = $this->normaliseTime($match[2], $match[3] ?? '', $match[4]);
        }
        if ($time === null) {
            $fallback = '/\b(saturday|tuesday)\b[^\.\n]{0,140}?\b(\d{1,2})(?::(\d{2}))?\s*(a\.?m\.?|p\.?m\.?)\b/iu';
            if (preg_match($fallback, $text, $match)) {
                $weekday = ucfirst(strtolower($match[1]));
                $time = $this->normaliseTime($match[2], $match[3] ?? '', $match[4]);
            }
        }

        $durationMin = null;
        $durationMax = null;
        if (preg_match('/(\d{1,3})\s*(?:-|–|to)\s*(\d{1,3})\s*minutes?/iu', $text, $duration)) {
            $durationMin = (int) $duration[1];
            $durationMax = (int) $duration[2];
        } elseif (preg_match('/(?:last|duration)[^\d]{0,20}(\d{1,3})\s*minutes?/iu', $text, $duration)) {
            $durationMin = $durationMax = (int) $duration[1];
        }

        if ($weekday === null || $time === null) {
            return [];
        }

        $mechanics = [];
        foreach ([
            'Luck multipliers' => '/luck|multiplier/iu',
            'Lucky Blocks' => '/lucky\s+blocks?/iu',
            'Limited machines or events' => '/limited\s+(?:machine|event)|machine/iu',
            'Rare Brainrot spawns' => '/rare\s+(?:brainrot|spawn)|spawn/iu',
        ] as $label => $regex) {
            if (preg_match($regex, $text)) {
                $mechanics[] = $label;
            }
        }

        $next = $this->nextOccurrence($weekday, $time, CarbonImmutable::now(self::TIMEZONE));

        return [
            'weekday' => $weekday,
            'time' => $time,
            'timezone' => self::TIMEZONE,
            'next_event_at' => $next?->toIso8601String(),
            'source_url' => $key === 'taco_tuesday' ? self::TACO_TUESDAY_SOURCE : self::ADMIN_ABUSE_SOURCE,
            'duration_min_minutes' => $durationMin,
            'duration_max_minutes' => $durationMax,
            'status' => 'confirmed',
            'source' => 'community',
            'mechanics' => $mechanics,
        ];
    }

    private function normaliseTime(string $hour, string $minute, string $meridiem): ?string
    {
        $h = (int) $hour;
        $m = $minute === '' ? 0 : (int) $minute;
        if ($h < 1 || $h > 12 || $m < 0 || $m > 59) {
            return null;
        }
        $meridiem = strtolower(str_replace('.', '', $meridiem));
        if ($meridiem === 'pm' && $h < 12) {
            $h += 12;
        } elseif ($meridiem === 'am' && $h === 12) {
            $h = 0;
        }

        return sprintf('%02d:%02d', $h, $m);
    }

    /** @return array<string, mixed>|null */
    private function displayEvent(mixed $raw, string $label, CarbonImmutable $now): ?array
    {
        if (! is_array($raw) || ! isset($raw['weekday'], $raw['time'])) {
            return null;
        }
        if (in_array(strtolower((string) ($raw['status'] ?? '')), ['cancelled', 'canceled', 'unconfirmed', 'not_confirmed'], true)) {
            return null;
        }
        $weekday = ucfirst(strtolower((string) $raw['weekday']));
        $time = (string) $raw['time'];
        if (! in_array($weekday, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], true)
            || ! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }

        $durationMax = (int) ($raw['duration_max_minutes'] ?? $raw['duration_max'] ?? 0);
        $durationMin = (int) ($raw['duration_min_minutes'] ?? $raw['duration_min'] ?? 0);
        $start = $this->parseDate($raw['next_event_at'] ?? null, self::TIMEZONE);
        if ($start === null || $start->lessThanOrEqualTo($now->subMinutes(1))) {
            $today = $this->sameDayOccurrence($weekday, $time, $now);
            $start = $today !== null && $durationMax > 0 && $now->lessThan($today->addMinutes($durationMax))
                ? $today
                : $this->nextOccurrence($weekday, $time, $now);
        }
        if ($start === null) {
            return null;
        }
        $end = $durationMax > 0 ? $start->addMinutes($durationMax) : null;
        $status = 'upcoming';
        if ($end !== null && $now->greaterThanOrEqualTo($start) && $now->lessThan($end)) {
            $status = 'live';
        } elseif ($start->isSameDay($now)) {
            $status = 'today';
        }
        $tzLabel = $start->format('T');

        return [
            'key' => strtolower(str_replace(' ', '_', $label)),
            'label' => $label,
            'weekday' => $weekday,
            'time' => $time,
            'recurrence_label' => 'Every '.$weekday.' at '.$this->formatTime($time).' '.$tzLabel,
            'eastern_time' => $start->format('M j, Y').' at '.$start->format('g:i A').' '.$tzLabel,
            'local_iso' => $start->toIso8601String(),
            'next_event_at' => $start->toIso8601String(),
            'date_label' => $start->format('l, F j, Y'),
            'duration_label' => $durationMin > 0 && $durationMax > 0
                ? ($durationMin === $durationMax ? $durationMin.' minutes' : $durationMin.'–'.$durationMax.' minutes')
                : 'Not confirmed',
            'duration_min_minutes' => $durationMin > 0 ? $durationMin : null,
            'duration_max_minutes' => $durationMax > 0 ? $durationMax : null,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'is_today' => $status === 'today' || $status === 'live',
            'is_live' => $status === 'live',
            'timezone' => self::TIMEZONE,
            'timezone_label' => $tzLabel,
            'source' => $raw['source'] ?? 'community',
            'event_status' => strtolower((string) ($raw['status'] ?? 'confirmed')),
        ];
    }

    /**
     * Apply a temporary stored date/status override without changing the
     * recurring source record. Unknown override shapes are ignored safely.
     *
     * @return array<string, mixed>
     */
    private function applyOverride(array $stored, string $key): array
    {
        $event = is_array($stored[$key] ?? null) ? $stored[$key] : [];
        $override = data_get($stored, 'overrides.'.$key);
        if (! is_array($override)) {
            $override = data_get($stored, 'temporary_overrides.'.$key);
        }
        if (is_array($override)) {
            $event = array_merge($event, $override);
        }

        if (isset($stored['cancelled']) && (bool) $stored['cancelled']) {
            $event['status'] = 'cancelled';
        }

        return $event;
    }

    private function nextOccurrence(string $weekday, string $time, CarbonImmutable $now): ?CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $candidate = $now->startOfDay()->next($weekday)->setTime($hour, $minute);
        if (strtolower($now->format('l')) === strtolower($weekday)) {
            $sameDay = $now->startOfDay()->setTime($hour, $minute);
            if ($sameDay->greaterThan($now)) {
                $candidate = $sameDay;
            }
        }

        return $candidate;
    }

    private function sameDayOccurrence(string $weekday, string $time, CarbonImmutable $now): ?CarbonImmutable
    {
        if (strtolower($now->format('l')) !== strtolower($weekday)) {
            return null;
        }
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $now->startOfDay()->setTime($hour, $minute);
    }

    private function parseDate(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($value, $timezone)->setTimezone(self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatTime(string $time): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return CarbonImmutable::create(2000, 1, 1, $hour, $minute, 0, self::TIMEZONE)->format('g:i A');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'live' => 'Live Now',
            'today' => 'Today',
            'upcoming' => 'Upcoming',
            default => 'Not Confirmed',
        };
    }

    private function extractSourceText(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }
        foreach ([
            data_get($payload, 'parse.wikitext.*'),
            data_get($payload, 'parse.text.*'),
            data_get($payload, 'parse.wikitext'),
            data_get($payload, 'parse.text'),
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
            if (is_array($candidate)) {
                $flat = $this->flattenStrings($candidate);
                if ($flat !== '') {
                    return $flat;
                }
            }
        }

        return '';
    }

    private function flattenStrings(array $values): string
    {
        $parts = [];
        array_walk_recursive($values, static function ($value) use (&$parts): void {
            if (is_string($value) && trim($value) !== '') {
                $parts[] = $value;
            }
        });

        return trim(implode("\n", $parts));
    }
}
