<?php
/*
 * Sublime PHP micro framework
 * https://github.com/nadermkhan/Sublime/
 * Developed by Nader Mahbub Khan
 * Initial release: December 25, 2025
 * Version : 1.0
 */
declare(strict_types=1);
namespace Nader\Sublime;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;
use PDO;
use PDOException;
use Stringable;
use Throwable;
use Exception;
use Closure;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * Lightweight Date/Time Utility Class
 * Zero-dependency, method chaining, immutable design
 *
 * @version 1.0.0
 */

class Date implements JsonSerializable, Stringable
{
    private int $timestamp;
    private string $timezone;

    private static string $defaultTimezone = 'Asia/Dhaka';

    private const SECONDS_PER_MINUTE = 60;
    private const SECONDS_PER_HOUR = 3600;
    private const SECONDS_PER_DAY = 86400;
    private const SECONDS_PER_WEEK = 604800;
    private const SECONDS_PER_MONTH = 2592000;
    private const SECONDS_PER_YEAR = 31536000;

    private const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    private const DAYS_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    private const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    private const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Human-readable format tokens
    private const FORMAT_MAP = [
        // Year
        'YYYY' => 'Y',      // 2024
        'YY' => 'y',        // 24

        // Month
        'MMMM' => 'F',      // January
        'MMM' => 'M',       // Jan
        'MM' => 'm',        // 01
        'M' => 'n',         // 1

        // Day
        'DDDD' => 'l',      // Monday
        'DDD' => 'D',       // Mon
        'DD' => 'd',        // 01
        'D' => 'j',         // 1

        // Hour
        'HH' => 'H',        // 00-23
        'H' => 'G',         // 0-23
        'hh' => 'h',        // 01-12
        'h' => 'g',         // 1-12

        // Minute
        'mm' => 'i',        // 00-59
        'm' => 'i',         // 00-59

        // Second
        'ss' => 's',        // 00-59
        's' => 's',         // 00-59

        // AM/PM
        'A' => 'A',         // AM/PM
        'a' => 'a',         // am/pm

        // Timezone
        'Z' => 'P',         // +06:00
        'z' => 'T',         // BST

        // Ordinal
        'Do' => 'jS',       // 1st, 2nd, 3rd

        // Week
        'W' => 'W',         // Week number

        // Day of year
        'DY' => 'z',        // 0-365
    ];

    // Preset formats
    private const PRESETS = [
        'date' => 'YYYY-MM-DD',
        'time' => 'HH:mm:ss',
        'datetime' => 'YYYY-MM-DD HH:mm:ss',
        'human' => 'MMMM D, YYYY',
        'humantime' => 'MMMM D, YYYY h:mm A',
        'short' => 'MMM D, YYYY',
        'shorttime' => 'MMM D, YYYY h:mm A',
        'long' => 'DDDD, MMMM D, YYYY',
        'longtime' => 'DDDD, MMMM D, YYYY h:mm A',
        'us' => 'MM/DD/YYYY',
        'eu' => 'DD/MM/YYYY',
        'iso' => 'YYYY-MM-DDTHH:mm:ssZ',
        'rss' => 'DDD, DD MMM YYYY HH:mm:ss Z',
        'db' => 'YYYY-MM-DD HH:mm:ss',
        'time12' => 'h:mm A',
        'time24' => 'HH:mm',
        'month' => 'MMMM YYYY',
        'monthshort' => 'MMM YYYY',
        'day' => 'DDDD',
        'dayshort' => 'DDD',
        'ordinal' => 'MMMM Do, YYYY',
    ];

    
    // CONFIGURATION
    

    public static function setDefaultTimezone(string $timezone): void
    {
        self::$defaultTimezone = $timezone;
        date_default_timezone_set($timezone);
    }

    public static function getDefaultTimezone(): string
    {
        return self::$defaultTimezone;
    }

    public static function useTimezone(string $timezone): void
    {
        self::setDefaultTimezone($timezone);
    }

    public static function useDhaka(): void
    {
        self::setDefaultTimezone('Asia/Dhaka');
    }

    public static function useUTC(): void
    {
        self::setDefaultTimezone('UTC');
    }

    public static function useLocal(): void
    {
        self::setDefaultTimezone(date_default_timezone_get());
    }

    
    // CONSTRUCTORS
    

    public function __construct(int|string|null $time = null, string $timezone = null)
    {
        $this->timezone = $timezone ?? self::$defaultTimezone;
        $previousTz = date_default_timezone_get();
        date_default_timezone_set($this->timezone);

        if ($time === null) {
            $this->timestamp = time();
        } elseif (is_int($time)) {
            $this->timestamp = $time;
        } else {
            $this->timestamp = strtotime($time) ?: time();
        }

        date_default_timezone_set($previousTz);
    }

    public static function now(string $timezone = null): self
    {
        return new self(null, $timezone);
    }

    public static function today(string $timezone = null): self
    {
        return self::now($timezone)->startOfDay();
    }

    public static function tomorrow(string $timezone = null): self
    {
        return self::now($timezone)->addDays(1)->startOfDay();
    }

    public static function yesterday(string $timezone = null): self
    {
        return self::now($timezone)->subDays(1)->startOfDay();
    }

    public static function parse(string $time, string $timezone = null): self
    {
        return new self($time, $timezone);
    }

    public static function fromTimestamp(int $timestamp, string $timezone = null): self
    {
        return new self($timestamp, $timezone);
    }

    public static function create(
        int $year = null,
        int $month = null,
        int $day = null,
        int $hour = null,
        int $minute = null,
        int $second = null,
        string $timezone = null
    ): self {
        $tz = $timezone ?? self::$defaultTimezone;
        $previousTz = date_default_timezone_get();
        date_default_timezone_set($tz);

        $timestamp = mktime(
            $hour ?? (int)date('H'),
            $minute ?? (int)date('i'),
            $second ?? (int)date('s'),
            $month ?? (int)date('m'),
            $day ?? (int)date('d'),
            $year ?? (int)date('Y')
        );

        date_default_timezone_set($previousTz);
        return new self($timestamp, $tz);
    }

    public static function createDate(int $year, int $month, int $day, string $timezone = null): self
    {
        return self::create($year, $month, $day, 0, 0, 0, $timezone);
    }

    public static function createTime(int $hour, int $minute, int $second = 0, string $timezone = null): self
    {
        return self::create(null, null, null, $hour, $minute, $second, $timezone);
    }

    
    // GETTERS
    

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function year(): int
    {
        return (int)$this->formatRaw('Y');
    }

    public function month(): int
    {
        return (int)$this->formatRaw('n');
    }

    public function day(): int
    {
        return (int)$this->formatRaw('j');
    }

    public function hour(): int
    {
        return (int)$this->formatRaw('G');
    }

    public function minute(): int
    {
        return (int)$this->formatRaw('i');
    }

    public function second(): int
    {
        return (int)$this->formatRaw('s');
    }

    public function dayOfWeek(): int
    {
        return (int)$this->formatRaw('w');
    }

    public function dayOfWeekIso(): int
    {
        return (int)$this->formatRaw('N');
    }

    public function dayOfYear(): int
    {
        return (int)$this->formatRaw('z') + 1;
    }

    public function weekOfYear(): int
    {
        return (int)$this->formatRaw('W');
    }

    public function weekOfMonth(): int
    {
        return (int)ceil($this->day() / 7);
    }

    public function daysInMonth(): int
    {
        return (int)$this->formatRaw('t');
    }

    public function quarter(): int
    {
        return (int)ceil($this->month() / 3);
    }

    public function age(): int
    {
        $now = self::now($this->timezone);
        $age = $now->year() - $this->year();

        if ($now->month() < $this->month() ||
            ($now->month() === $this->month() && $now->day() < $this->day())) {
            $age--;
        }

        return $age;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function offset(): int
    {
        return (int)$this->formatRaw('Z');
    }

    public function offsetString(): string
    {
        return $this->formatRaw('P');
    }

    public function dayName(): string
    {
        return self::DAYS[$this->dayOfWeek()];
    }

    public function shortDayName(): string
    {
        return self::DAYS_SHORT[$this->dayOfWeek()];
    }

    public function monthName(): string
    {
        return self::MONTHS[$this->month() - 1];
    }

    public function shortMonthName(): string
    {
        return self::MONTHS_SHORT[$this->month() - 1];
    }

    
    // SETTERS (IMMUTABLE)
    

    public function setYear(int $year): self
    {
        return self::create($year, $this->month(), $this->day(), $this->hour(), $this->minute(), $this->second(), $this->timezone);
    }

    public function setMonth(int $month): self
    {
        return self::create($this->year(), $month, $this->day(), $this->hour(), $this->minute(), $this->second(), $this->timezone);
    }

    public function setDay(int $day): self
    {
        return self::create($this->year(), $this->month(), $day, $this->hour(), $this->minute(), $this->second(), $this->timezone);
    }

    public function setHour(int $hour): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $hour, $this->minute(), $this->second(), $this->timezone);
    }

    public function setMinute(int $minute): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $this->hour(), $minute, $this->second(), $this->timezone);
    }

    public function setSecond(int $second): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $this->hour(), $this->minute(), $second, $this->timezone);
    }

    public function setDate(int $year, int $month, int $day): self
    {
        return self::create($year, $month, $day, $this->hour(), $this->minute(), $this->second(), $this->timezone);
    }

    public function setTime(int $hour, int $minute, int $second = 0): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $hour, $minute, $second, $this->timezone);
    }

    
    // TIMEZONE CONVERSION
    

    public function setTimezone(string $timezone): self
    {
        // Convert timestamp to new timezone
        $dt = new DateTime('@' . $this->timestamp);
        $dt->setTimezone(new DateTimeZone($timezone));

        $instance = clone $this;
        $instance->timezone = $timezone;
        return $instance;
    }

    public function toTimezone(string $timezone): self
    {
        return $this->setTimezone($timezone);
    }

    public function toUTC(): self
    {
        return $this->setTimezone('UTC');
    }

    public function toDhaka(): self
    {
        return $this->setTimezone('Asia/Dhaka');
    }

    public function toLocal(): self
    {
        return $this->setTimezone(self::$defaultTimezone);
    }

    public function toNewYork(): self
    {
        return $this->setTimezone('America/New_York');
    }

    public function toLondon(): self
    {
        return $this->setTimezone('Europe/London');
    }

    public function toTokyo(): self
    {
        return $this->setTimezone('Asia/Tokyo');
    }

    public function toDubai(): self
    {
        return $this->setTimezone('Asia/Dubai');
    }

    public function toSingapore(): self
    {
        return $this->setTimezone('Asia/Singapore');
    }

    public function toSydney(): self
    {
        return $this->setTimezone('Australia/Sydney');
    }

    public function toParis(): self
    {
        return $this->setTimezone('Europe/Paris');
    }

    public function toLA(): self
    {
        return $this->setTimezone('America/Los_Angeles');
    }

    public function toChicago(): self
    {
        return $this->setTimezone('America/Chicago');
    }

    public function toKolkata(): self
    {
        return $this->setTimezone('Asia/Kolkata');
    }

    public function toHongKong(): self
    {
        return $this->setTimezone('Asia/Hong_Kong');
    }

    public function toMoscow(): self
    {
        return $this->setTimezone('Europe/Moscow');
    }

    public function toBerlin(): self
    {
        return $this->setTimezone('Europe/Berlin');
    }

    public function toJakarta(): self
    {
        return $this->setTimezone('Asia/Jakarta');
    }

    public function toSeoul(): self
    {
        return $this->setTimezone('Asia/Seoul');
    }

    public function toShanghai(): self
    {
        return $this->setTimezone('Asia/Shanghai');
    }

    public function toMumbai(): self
    {
        return $this->setTimezone('Asia/Kolkata');
    }

    public static function listTimezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    public static function listTimezonesByRegion(string $region = 'Asia'): array
    {
        $all = DateTimeZone::listIdentifiers();
        return array_filter($all, fn($tz) => str_starts_with($tz, $region));
    }

    
    // ADDITION
    

    public function addYears(int $years): self
    {
        return $this->modify("+{$years} years");
    }

    public function addYear(): self
    {
        return $this->addYears(1);
    }

    public function addMonths(int $months): self
    {
        return $this->modify("+{$months} months");
    }

    public function addMonth(): self
    {
        return $this->addMonths(1);
    }

    public function addWeeks(int $weeks): self
    {
        return $this->modify("+{$weeks} weeks");
    }

    public function addWeek(): self
    {
        return $this->addWeeks(1);
    }

    public function addDays(int $days): self
    {
        return $this->modify("+{$days} days");
    }

    public function addDay(): self
    {
        return $this->addDays(1);
    }

    public function addHours(int $hours): self
    {
        return new self($this->timestamp + ($hours * self::SECONDS_PER_HOUR), $this->timezone);
    }

    public function addHour(): self
    {
        return $this->addHours(1);
    }

    public function addMinutes(int $minutes): self
    {
        return new self($this->timestamp + ($minutes * self::SECONDS_PER_MINUTE), $this->timezone);
    }

    public function addMinute(): self
    {
        return $this->addMinutes(1);
    }

    public function addSeconds(int $seconds): self
    {
        return new self($this->timestamp + $seconds, $this->timezone);
    }

    public function addSecond(): self
    {
        return $this->addSeconds(1);
    }

    public function add(int $value, string $unit): self
    {
        return match(strtolower(rtrim($unit, 's'))) {
            'year' => $this->addYears($value),
            'month' => $this->addMonths($value),
            'week' => $this->addWeeks($value),
            'day' => $this->addDays($value),
            'hour' => $this->addHours($value),
            'minute' => $this->addMinutes($value),
            'second' => $this->addSeconds($value),
            default => throw new InvalidArgumentException("Unknown unit: {$unit}")
        };
    }

    private function modify(string $modifier): self
    {
        $previousTz = date_default_timezone_get();
        date_default_timezone_set($this->timezone);
        $newTimestamp = strtotime($modifier, $this->timestamp);
        date_default_timezone_set($previousTz);
        return new self($newTimestamp, $this->timezone);
    }

    
    // SUBTRACTION
    

    public function subYears(int $years): self
    {
        return $this->modify("-{$years} years");
    }

    public function subYear(): self
    {
        return $this->subYears(1);
    }

    public function subMonths(int $months): self
    {
        return $this->modify("-{$months} months");
    }

    public function subMonth(): self
    {
        return $this->subMonths(1);
    }

    public function subWeeks(int $weeks): self
    {
        return $this->modify("-{$weeks} weeks");
    }

    public function subWeek(): self
    {
        return $this->subWeeks(1);
    }

    public function subDays(int $days): self
    {
        return $this->modify("-{$days} days");
    }

    public function subDay(): self
    {
        return $this->subDays(1);
    }

    public function subHours(int $hours): self
    {
        return $this->addHours(-$hours);
    }

    public function subHour(): self
    {
        return $this->subHours(1);
    }

    public function subMinutes(int $minutes): self
    {
        return $this->addMinutes(-$minutes);
    }

    public function subMinute(): self
    {
        return $this->subMinutes(1);
    }

    public function subSeconds(int $seconds): self
    {
        return $this->addSeconds(-$seconds);
    }

    public function subSecond(): self
    {
        return $this->subSeconds(1);
    }

    public function sub(int $value, string $unit): self
    {
        return $this->add(-$value, $unit);
    }

    
    // BOUNDARIES
    

    public function startOfDay(): self
    {
        return self::create($this->year(), $this->month(), $this->day(), 0, 0, 0, $this->timezone);
    }

    public function endOfDay(): self
    {
        return self::create($this->year(), $this->month(), $this->day(), 23, 59, 59, $this->timezone);
    }

    public function startOfMonth(): self
    {
        return self::create($this->year(), $this->month(), 1, 0, 0, 0, $this->timezone);
    }

    public function endOfMonth(): self
    {
        return self::create($this->year(), $this->month(), $this->daysInMonth(), 23, 59, 59, $this->timezone);
    }

    public function startOfYear(): self
    {
        return self::create($this->year(), 1, 1, 0, 0, 0, $this->timezone);
    }

    public function endOfYear(): self
    {
        return self::create($this->year(), 12, 31, 23, 59, 59, $this->timezone);
    }

    public function startOfWeek(): self
    {
        $dayOfWeek = $this->dayOfWeek();
        $diff = $dayOfWeek === 0 ? 6 : $dayOfWeek - 1;
        return $this->subDays($diff)->startOfDay();
    }

    public function endOfWeek(): self
    {
        return $this->startOfWeek()->addDays(6)->endOfDay();
    }

    public function startOfHour(): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $this->hour(), 0, 0, $this->timezone);
    }

    public function endOfHour(): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $this->hour(), 59, 59, $this->timezone);
    }

    public function startOfMinute(): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $this->hour(), $this->minute(), 0, $this->timezone);
    }

    public function endOfMinute(): self
    {
        return self::create($this->year(), $this->month(), $this->day(), $this->hour(), $this->minute(), 59, $this->timezone);
    }

    public function startOfQuarter(): self
    {
        $month = ($this->quarter() - 1) * 3 + 1;
        return self::create($this->year(), $month, 1, 0, 0, 0, $this->timezone);
    }

    public function endOfQuarter(): self
    {
        $month = $this->quarter() * 3;
        return self::create($this->year(), $month, 1, 0, 0, 0, $this->timezone)->endOfMonth();
    }

    
    // DIFFERENCE
    

    public function diffInSeconds(self|string|int $date = null): int
    {
        $date = $this->resolveDate($date);
        return abs($this->timestamp - $date->timestamp());
    }

    public function diffInMinutes(self|string|int $date = null): int
    {
        return (int)floor($this->diffInSeconds($date) / self::SECONDS_PER_MINUTE);
    }

    public function diffInHours(self|string|int $date = null): int
    {
        return (int)floor($this->diffInSeconds($date) / self::SECONDS_PER_HOUR);
    }

    public function diffInDays(self|string|int $date = null): int
    {
        return (int)floor($this->diffInSeconds($date) / self::SECONDS_PER_DAY);
    }

    public function diffInWeeks(self|string|int $date = null): int
    {
        return (int)floor($this->diffInDays($date) / 7);
    }

    public function diffInMonths(self|string|int $date = null): int
    {
        $date = $this->resolveDate($date);
        $years = abs($this->year() - $date->year());
        $months = $this->month() - $date->month();
        return abs($years * 12 + $months);
    }

    public function diffInYears(self|string|int $date = null): int
    {
        $date = $this->resolveDate($date);
        return abs($this->year() - $date->year());
    }

        public function diffForHumans(self|string|int $date = null, bool $absolute = false): string
    {
        $date = $this->resolveDate($date);
        $seconds = $this->timestamp - $date->timestamp();

        $isFuture = $seconds > 0;  // Fixed: was $seconds < 0
        $seconds = abs($seconds);

        [$value, $unit] = match(true) {
            $seconds < 30 => [0, 'just now'],
            $seconds < 60 => [$seconds, 'second'],
            $seconds < self::SECONDS_PER_HOUR => [(int)floor($seconds / 60), 'minute'],
            $seconds < self::SECONDS_PER_DAY => [(int)floor($seconds / self::SECONDS_PER_HOUR), 'hour'],
            $seconds < self::SECONDS_PER_WEEK => [(int)floor($seconds / self::SECONDS_PER_DAY), 'day'],
            $seconds < self::SECONDS_PER_MONTH => [(int)floor($seconds / self::SECONDS_PER_WEEK), 'week'],
            $seconds < self::SECONDS_PER_YEAR => [(int)floor($seconds / self::SECONDS_PER_MONTH), 'month'],
            default => [(int)floor($seconds / self::SECONDS_PER_YEAR), 'year']
        };

        if ($unit === 'just now') {
            return 'just now';
        }

        $unitPlural = $value === 1 ? $unit : $unit . 's';
        $result = "{$value} {$unitPlural}";

        if ($absolute) {
            return $result;
        }

        return $isFuture ? "in {$result}" : "{$result} ago";
    }

    public function ago(): string
    {
        return $this->diffForHumans();
    }

    public function fromNow(): string
    {
        return $this->diffForHumans();
    }

    public function timeAgo(): string
    {
        return $this->diffForHumans();
    }

    private function resolveDate(self|string|int|null $date): self
    {
        if ($date === null) {
            return self::now($this->timezone);
        }
        if ($date instanceof self) {
            return $date;
        }
        return new self($date, $this->timezone);
    }

    
    // COMPARISON
    

    public function eq(self|string|int $date): bool
    {
        return $this->timestamp === $this->resolveDate($date)->timestamp();
    }

    public function equals(self|string|int $date): bool
    {
        return $this->eq($date);
    }

    public function ne(self|string|int $date): bool
    {
        return !$this->eq($date);
    }

    public function notEquals(self|string|int $date): bool
    {
        return $this->ne($date);
    }

    public function gt(self|string|int $date): bool
    {
        return $this->timestamp > $this->resolveDate($date)->timestamp();
    }

    public function greaterThan(self|string|int $date): bool
    {
        return $this->gt($date);
    }

    public function isAfter(self|string|int $date): bool
    {
        return $this->gt($date);
    }

    public function gte(self|string|int $date): bool
    {
        return $this->timestamp >= $this->resolveDate($date)->timestamp();
    }

    public function greaterThanOrEquals(self|string|int $date): bool
    {
        return $this->gte($date);
    }

    public function isAfterOrEqual(self|string|int $date): bool
    {
        return $this->gte($date);
    }

    public function lt(self|string|int $date): bool
    {
        return $this->timestamp < $this->resolveDate($date)->timestamp();
    }

    public function lessThan(self|string|int $date): bool
    {
        return $this->lt($date);
    }

    public function isBefore(self|string|int $date): bool
    {
        return $this->lt($date);
    }

    public function lte(self|string|int $date): bool
    {
        return $this->timestamp <= $this->resolveDate($date)->timestamp();
    }

    public function lessThanOrEquals(self|string|int $date): bool
    {
        return $this->lte($date);
    }

    public function isBeforeOrEqual(self|string|int $date): bool
    {
        return $this->lte($date);
    }

    public function between(self|string|int $start, self|string|int $end, bool $equal = true): bool
    {
        $start = $this->resolveDate($start);
        $end = $this->resolveDate($end);

        return $equal
            ? ($this->gte($start) && $this->lte($end))
            : ($this->gt($start) && $this->lt($end));
    }

    public function isBetween(self|string|int $start, self|string|int $end): bool
    {
        return $this->between($start, $end);
    }

    public function closest(self|string|int $date1, self|string|int $date2): self
    {
        $d1 = $this->resolveDate($date1);
        $d2 = $this->resolveDate($date2);
        return $this->diffInSeconds($d1) < $this->diffInSeconds($d2) ? $d1 : $d2;
    }

    public function farthest(self|string|int $date1, self|string|int $date2): self
    {
        $d1 = $this->resolveDate($date1);
        $d2 = $this->resolveDate($date2);
        return $this->diffInSeconds($d1) > $this->diffInSeconds($d2) ? $d1 : $d2;
    }

    public function min(self|string|int $date): self
    {
        $date = $this->resolveDate($date);
        return $this->lt($date) ? clone $this : $date;
    }

    public function max(self|string|int $date): self
    {
        $date = $this->resolveDate($date);
        return $this->gt($date) ? clone $this : $date;
    }

    
    // BOOLEAN CHECKS
    

    public function isPast(): bool
    {
        return $this->lt(self::now($this->timezone));
    }

    public function isFuture(): bool
    {
        return $this->gt(self::now($this->timezone));
    }

    public function isToday(): bool
    {
        return $this->toDate() === self::now($this->timezone)->toDate();
    }

    public function isTomorrow(): bool
    {
        return $this->toDate() === self::tomorrow($this->timezone)->toDate();
    }

    public function isYesterday(): bool
    {
        return $this->toDate() === self::yesterday($this->timezone)->toDate();
    }

    public function isThisYear(): bool
    {
        return $this->year() === self::now($this->timezone)->year();
    }

    public function isThisMonth(): bool
    {
        return $this->format('month') === self::now($this->timezone)->format('month');
    }

    public function isThisWeek(): bool
    {
        $now = self::now($this->timezone);
        return $this->weekOfYear() === $now->weekOfYear() && $this->year() === $now->year();
    }

    public function isWeekday(): bool
    {
        return $this->dayOfWeek() >= 1 && $this->dayOfWeek() <= 5;
    }

    public function isWeekend(): bool
    {
        return $this->dayOfWeek() === 0 || $this->dayOfWeek() === 6;
    }

    public function isMonday(): bool { return $this->dayOfWeek() === 1; }
    public function isTuesday(): bool { return $this->dayOfWeek() === 2; }
    public function isWednesday(): bool { return $this->dayOfWeek() === 3; }
    public function isThursday(): bool { return $this->dayOfWeek() === 4; }
    public function isFriday(): bool { return $this->dayOfWeek() === 5; }
    public function isSaturday(): bool { return $this->dayOfWeek() === 6; }
    public function isSunday(): bool { return $this->dayOfWeek() === 0; }

    public function isLeapYear(): bool
    {
        $year = $this->year();
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }

    public function isSameDay(self|string|int $date): bool
    {
        return $this->toDate() === $this->resolveDate($date)->toDate();
    }

    public function isSameMonth(self|string|int $date): bool
    {
        $date = $this->resolveDate($date);
        return $this->year() === $date->year() && $this->month() === $date->month();
    }

    public function isSameYear(self|string|int $date): bool
    {
        return $this->year() === $this->resolveDate($date)->year();
    }

    public function isBirthday(self|string|int $date = null): bool
    {
        $date = $date ? $this->resolveDate($date) : self::now($this->timezone);
        return $this->month() === $date->month() && $this->day() === $date->day();
    }

    public function isMorning(): bool
    {
        return $this->hour() >= 5 && $this->hour() < 12;
    }

    public function isAfternoon(): bool
    {
        return $this->hour() >= 12 && $this->hour() < 17;
    }

    public function isEvening(): bool
    {
        return $this->hour() >= 17 && $this->hour() < 21;
    }

    public function isNight(): bool
    {
        return $this->hour() >= 21 || $this->hour() < 5;
    }

    
    // FORMATTING
    

    private function formatRaw(string $format): string
    {
        $previousTz = date_default_timezone_get();
        date_default_timezone_set($this->timezone);
        $result = date($format, $this->timestamp);
        date_default_timezone_set($previousTz);
        return $result;
    }

    private function translateFormat(string $format): string
    {
        // Check if it's a preset
        if (isset(self::PRESETS[$format])) {
            $format = self::PRESETS[$format];
        }

        // Sort tokens by length (longest first) to avoid partial replacements
        $tokens = self::FORMAT_MAP;
        uksort($tokens, fn($a, $b) => strlen($b) - strlen($a));

        // Escape literal text in brackets
        $escaped = [];
        $format = preg_replace_callback('/\[([^\]]+)\]/', function($matches) use (&$escaped) {
            $key = '___ESC' . count($escaped) . '___';
            $escaped[$key] = $matches[1];
            return $key;
        }, $format);

        // Replace tokens with placeholders
        $placeholders = [];
        foreach ($tokens as $token => $phpFormat) {
            if (str_contains($format, $token)) {
                $key = '##' . count($placeholders) . '##';
                $placeholders[$key] = $phpFormat;
                $format = str_replace($token, $key, $format);
            }
        }

        // Replace placeholders with PHP format
        foreach ($placeholders as $key => $phpFormat) {
            $format = str_replace($key, $phpFormat, $format);
        }

        // Restore escaped text
        foreach ($escaped as $key => $value) {
            $format = str_replace($key, $value, $format);
        }

        return $format;
    }

    public function format(string $format): string
    {
        $phpFormat = $this->translateFormat($format);
        return $this->formatRaw($phpFormat);
    }

    // Preset format methods
    public function toDate(): string
    {
        return $this->format('date');
    }

    public function toTime(): string
    {
        return $this->format('time');
    }

    public function toDateTime(): string
    {
        return $this->format('datetime');
    }

    public function toHuman(): string
    {
        return $this->format('human');
    }

    public function toHumanTime(): string
    {
        return $this->format('humantime');
    }

    public function toShort(): string
    {
        return $this->format('short');
    }

    public function toShortTime(): string
    {
        return $this->format('shorttime');
    }

    public function toLong(): string
    {
        return $this->format('long');
    }

    public function toLongTime(): string
    {
        return $this->format('longtime');
    }

    public function toUS(): string
    {
        return $this->format('us');
    }

    public function toEU(): string
    {
        return $this->format('eu');
    }

    public function toISO(): string
    {
        return $this->format('iso');
    }

    public function toRSS(): string
    {
        return $this->format('rss');
    }

    public function toDb(): string
    {
        return $this->format('db');
    }

    public function toTime12(): string
    {
        return $this->format('time12');
    }

    public function toTime24(): string
    {
        return $this->format('time24');
    }

    public function toMonth(): string
    {
        return $this->format('month');
    }

    public function toMonthShort(): string
    {
        return $this->format('monthshort');
    }

    public function toOrdinal(): string
    {
        return $this->format('ordinal');
    }

    public function toArray(): array
    {
        return [
            'year' => $this->year(),
            'month' => $this->month(),
            'day' => $this->day(),
            'hour' => $this->hour(),
            'minute' => $this->minute(),
            'second' => $this->second(),
            'dayOfWeek' => $this->dayOfWeek(),
            'dayOfYear' => $this->dayOfYear(),
            'weekOfYear' => $this->weekOfYear(),
            'daysInMonth' => $this->daysInMonth(),
            'timestamp' => $this->timestamp,
            'timezone' => $this->timezone,
            'offset' => $this->offsetString(),
            'dayName' => $this->dayName(),
            'monthName' => $this->monthName(),
            'formatted' => $this->toDateTime(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toISO());
    }

    public function jsonSerialize(): string
    {
        return $this->toISO();
    }

    public function __toString(): string
    {
        return $this->toDateTime();
    }

    
    // NEXT/PREVIOUS
    

    public function next(string $dayName): self
    {
        return $this->modify("next {$dayName}");
    }

    public function previous(string $dayName): self
    {
        return $this->modify("last {$dayName}");
    }

    public function nextMonday(): self { return $this->next('monday'); }
    public function nextTuesday(): self { return $this->next('tuesday'); }
    public function nextWednesday(): self { return $this->next('wednesday'); }
    public function nextThursday(): self { return $this->next('thursday'); }
    public function nextFriday(): self { return $this->next('friday'); }
    public function nextSaturday(): self { return $this->next('saturday'); }
    public function nextSunday(): self { return $this->next('sunday'); }

    public function previousMonday(): self { return $this->previous('monday'); }
    public function previousTuesday(): self { return $this->previous('tuesday'); }
    public function previousWednesday(): self { return $this->previous('wednesday'); }
    public function previousThursday(): self { return $this->previous('thursday'); }
    public function previousFriday(): self { return $this->previous('friday'); }
    public function previousSaturday(): self { return $this->previous('saturday'); }
    public function previousSunday(): self { return $this->previous('sunday'); }

    public function nextWeekday(): self
    {
        $date = $this->addDay();
        while ($date->isWeekend()) {
            $date = $date->addDay();
        }
        return $date;
    }

    public function previousWeekday(): self
    {
        $date = $this->subDay();
        while ($date->isWeekend()) {
            $date = $date->subDay();
        }
        return $date;
    }

    
    // CLONING
    

    public function copy(): self
    {
        return clone $this;
    }

    public function clone(): self
    {
        return clone $this;
    }

    
    // STATIC HELPERS
    

    public static function range(self|string $start, self|string $end, int $step = 1, string $unit = 'day'): array
    {
        $start = $start instanceof self ? $start : self::parse($start);
        $end = $end instanceof self ? $end : self::parse($end);

        $dates = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dates[] = $current->copy();
            $current = $current->add($step, $unit);
        }

        return $dates;
    }

    public static function days(self|string $start, self|string $end): array
    {
        return self::range($start, $end, 1, 'day');
    }

    public static function weeks(self|string $start, self|string $end): array
    {
        return self::range($start, $end, 1, 'week');
    }

    public static function months(self|string $start, self|string $end): array
    {
        return self::range($start, $end, 1, 'month');
    }

    public static function years(self|string $start, self|string $end): array
    {
        return self::range($start, $end, 1, 'year');
    }

    
    // CALENDAR HELPERS
    

    public function isFirstDayOfMonth(): bool
    {
        return $this->day() === 1;
    }

    public function isLastDayOfMonth(): bool
    {
        return $this->day() === $this->daysInMonth();
    }

    public function isFirstDayOfYear(): bool
    {
        return $this->month() === 1 && $this->day() === 1;
    }

    public function isLastDayOfYear(): bool
    {
        return $this->month() === 12 && $this->day() === 31;
    }

    public function nthOfMonth(int $n, string $dayName): self
    {
        $date = $this->startOfMonth();
        $count = 0;

        while ($count < $n) {
            if (strtolower($date->dayName()) === strtolower($dayName)) {
                $count++;
                if ($count === $n) {
                    return $date;
                }
            }
            $date = $date->addDay();

            if ($date->month() !== $this->month()) {
                throw new InvalidArgumentException("No {$n}th {$dayName} in this month");
            }
        }

        return $date;
    }

    public function firstOfMonth(string $dayName = null): self
    {
        if ($dayName === null) {
            return $this->startOfMonth();
        }
        return $this->nthOfMonth(1, $dayName);
    }

    public function lastOfMonth(string $dayName = null): self
    {
        if ($dayName === null) {
            return $this->endOfMonth();
        }

        $date = $this->endOfMonth();
        while (strtolower($date->dayName()) !== strtolower($dayName)) {
            $date = $date->subDay();
        }
        return $date;
    }

    public function toDbString(): string
    {
        return $this->formatRaw('Y-m-d H:i:s');
    }
}

====
// HELPER FUNCTIONS
====

function now(string $timezone = null): Date
{
    return Date::now($timezone);
}

function today(string $timezone = null): Date
{
    return Date::today($timezone);
}

function tomorrow(string $timezone = null): Date
{
    return Date::tomorrow($timezone);
}

function yesterday(string $timezone = null): Date
{
    return Date::yesterday($timezone);
}

function parseDate(string $time, string $timezone = null): Date
{
    return Date::parse($time, $timezone);
}

class DB
{
    private static ?PDO $pdo = null;
    private static string $path = 'database.sqlite';
    private static array $config = [
        'busy_timeout' => 30000,
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'cache_size' => -64000,
        'temp_store' => 'MEMORY',
        'mmap_size' => 268435456,
        'page_size' => 4096,
    ];
    private static int $maxRetries = 5;
    private static int $retryDelay = 50000;
    private static int $transactionDepth = 0;

    public static function connect(string $path = null, array $config = []): PDO
    {
        if ($path) self::$path = $path;
        self::$config = array_merge(self::$config, $config);

        if (!self::$pdo) {
            self::$pdo = self::createConnection();
        }

        return self::$pdo;
    }

    private static function createConnection(): PDO
    {
        $pdo = new PDO('sqlite:' . self::$path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        $pdo->exec('PRAGMA busy_timeout = ' . self::$config['busy_timeout']);
        $pdo->exec('PRAGMA journal_mode = ' . self::$config['journal_mode']);
        $pdo->exec('PRAGMA synchronous = ' . self::$config['synchronous']);
        $pdo->exec('PRAGMA cache_size = ' . self::$config['cache_size']);
        $pdo->exec('PRAGMA temp_store = ' . self::$config['temp_store']);
        $pdo->exec('PRAGMA mmap_size = ' . self::$config['mmap_size']);
        $pdo->exec('PRAGMA page_size = ' . self::$config['page_size']);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA read_uncommitted = ON');

        return $pdo;
    }

    public static function pdo(): PDO
    {
        return self::$pdo ?? self::connect();
    }

    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    public static function schema(): SchemaBuilder
    {
        return new SchemaBuilder();
    }

    public static function raw(string $sql, array $bindings = []): array
    {
        return self::executeWithRetry(function() use ($sql, $bindings) {
            $stmt = self::pdo()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->fetchAll();
        });
    }

    public static function exec(string $sql): int
    {
        return self::executeWithRetry(function() use ($sql) {
            return self::pdo()->exec($sql);
        });
    }

    public static function transaction(callable $callback): mixed
    {
        self::$transactionDepth++;
        
        try {
            if (self::$transactionDepth === 1) {
                self::pdo()->beginTransaction();
            }
            
            $result = $callback(self::pdo());
            
            if (self::$transactionDepth === 1) {
                self::pdo()->commit();
            }
            
            self::$transactionDepth--;
            return $result;
            
        } catch (Throwable $e) {
            if (self::$transactionDepth === 1 && self::pdo()->inTransaction()) {
                self::pdo()->rollBack();
            }
            self::$transactionDepth--;
            throw $e;
        }
    }

    public static function inTransaction(): bool
    {
        return self::$transactionDepth > 0;
    }

    public static function executeWithRetry(callable $operation): mixed
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::$maxRetries) {
            try {
                return $operation();
            } catch (PDOException $e) {
                $lastException = $e;

                if (self::isRetryableError($e)) {
                    $attempts++;
                    $delay = self::$retryDelay * pow(2, $attempts - 1);
                    $jitter = rand(0, (int)($delay * 0.1));
                    usleep($delay + $jitter);
                    continue;
                }

                throw $e;
            }
        }

        throw new DatabaseException(
            "Operation failed after {$attempts} attempts: " . $lastException->getMessage(),
            (int)$lastException->getCode(),
            $lastException
        );
    }

    private static function isRetryableError(PDOException $e): bool
    {
        $message = strtolower($e->getMessage());
        $retryableMessages = [
            'database is locked',
            'database table is locked',
            'busy',
        ];

        foreach ($retryableMessages as $retryable) {
            if (str_contains($message, $retryable)) {
                return true;
            }
        }

        return false;
    }

    public static function checkpoint(): void
    {
        self::pdo()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
    }

    public static function optimize(): void
    {
        self::pdo()->exec('PRAGMA optimize');
        self::pdo()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        self::pdo()->exec('VACUUM');
        self::pdo()->exec('ANALYZE');
    }

    public static function close(): void
    {
        if (self::$pdo) {
            try {
                self::checkpoint();
            } catch (Throwable $e) {
                // Ignore checkpoint errors on close
            }
            self::$pdo = null;
            self::$transactionDepth = 0;
        }
    }

    public static function setMaxRetries(int $retries): void
    {
        self::$maxRetries = $retries;
    }

    public static function setRetryDelay(int $microseconds): void
    {
        self::$retryDelay = $microseconds;
    }
}

class DatabaseException extends Exception {}

class SchemaBuilder
{
    public function create(string $table, callable $callback): self
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        DB::transaction(function() use ($blueprint) {
            DB::exec($blueprint->toSql());
            foreach ($blueprint->getIndexSql() as $indexSql) {
                DB::exec($indexSql);
            }
        });
        return $this;
    }

    public function drop(string $table): self
    {
        DB::exec("DROP TABLE IF EXISTS {$table}");
        return $this;
    }

    public function dropAll(): self
    {
        DB::transaction(function() {
            $tables = $this->tables();
            foreach ($tables as $table) {
                $this->drop($table);
            }
        });
        return $this;
    }

    public function hasTable(string $table): bool
    {
        $result = DB::raw(
            "SELECT name FROM sqlite_master WHERE type='table' AND name=?",
            [$table]
        );
        return count($result) > 0;
    }

    public function hasColumn(string $table, string $column): bool
    {
        $columns = DB::raw("PRAGMA table_info({$table})");
        foreach ($columns as $col) {
            if ($col->name === $column) return true;
        }
        return false;
    }

    public function tables(): array
    {
        return array_map(
            fn($t) => $t->name,
            DB::raw("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
        );
    }

    public function addColumn(string $table, string $column, callable $callback): self
    {
        $col = new Column($column, 'TEXT');
        $callback($col);
        DB::exec("ALTER TABLE {$table} ADD COLUMN " . $col->toSql());
        return $this;
    }

    public function renameTable(string $from, string $to): self
    {
        DB::exec("ALTER TABLE {$from} RENAME TO {$to}");
        return $this;
    }
}

class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $indexes = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): Column
    {
        return $this->addColumn($name, 'INTEGER PRIMARY KEY AUTOINCREMENT');
    }

    public function uuid(string $name = 'uuid'): Column
    {
        return $this->addColumn($name, 'VARCHAR(36)');
    }

    public function string(string $name, int $length = 255): Column
    {
        return $this->addColumn($name, "VARCHAR({$length})");
    }

    public function text(string $name): Column
    {
        return $this->addColumn($name, 'TEXT');
    }

    public function integer(string $name): Column
    {
        return $this->addColumn($name, 'INTEGER');
    }

    public function bigInteger(string $name): Column
    {
        return $this->addColumn($name, 'BIGINT');
    }

    public function tinyInteger(string $name): Column
    {
        return $this->addColumn($name, 'TINYINT');
    }

    public function float(string $name): Column
    {
        return $this->addColumn($name, 'REAL');
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): Column
    {
        return $this->addColumn($name, "DECIMAL({$precision},{$scale})");
    }

    public function boolean(string $name): Column
    {
        return $this->addColumn($name, 'BOOLEAN');
    }

    public function date(string $name): Column
    {
        return $this->addColumn($name, 'DATE');
    }

    public function datetime(string $name): Column
    {
        return $this->addColumn($name, 'DATETIME');
    }

    public function timestamp(string $name): Column
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    public function timestamps(): self
    {
        $this->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        $this->datetime('updated_at')->nullable();
        return $this;
    }

    public function softDeletes(): self
    {
        $this->datetime('deleted_at')->nullable();
        return $this;
    }

    public function json(string $name): Column
    {
        return $this->addColumn($name, 'JSON');
    }

    public function blob(string $name): Column
    {
        return $this->addColumn($name, 'BLOB');
    }

    public function foreignId(string $name): Column
    {
        return $this->addColumn($name, 'INTEGER');
    }

    public function index(string|array ...$columns): self
    {
        // Flatten in case arrays are passed
        $cols = [];
        foreach ($columns as $col) {
            if (is_array($col)) {
                $cols = array_merge($cols, $col);
            } else {
                $cols[] = $col;
            }
        }

        $name = 'idx_' . $this->table . '_' . implode('_', $cols);
        $this->indexes[] = "CREATE INDEX IF NOT EXISTS {$name} ON {$this->table} (" . implode(', ', $cols) . ")";
        return $this;
    }
    public function unique(string ...$columns): self
    {
        $name = 'uniq_' . $this->table . '_' . implode('_', $columns);
        $this->indexes[] = "CREATE UNIQUE INDEX IF NOT EXISTS {$name} ON {$this->table} (" . implode(', ', $columns) . ")";
        return $this;
    }

    private function addColumn(string $name, string $type): Column
    {
        $column = new Column($name, $type);
        $this->columns[] = $column;
        return $column;
    }

    public function toSql(): string
    {
        $columnsSql = implode(",\n    ", array_map(fn($c) => $c->toSql(), $this->columns));
        return "CREATE TABLE IF NOT EXISTS {$this->table} (\n    {$columnsSql}\n)";
    }

    public function getIndexSql(): array
    {
        return $this->indexes;
    }
}

class Column
{
    private string $name;
    private string $type;
    private bool $nullable = false;
    private mixed $default = null;
    private bool $hasDefault = false;
    private bool $isRawDefault = false;
    private bool $unique = false;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;
        if (is_string($value) && in_array(strtoupper($value), ['CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME', 'NULL'])) {
            $this->isRawDefault = true;
        }
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function toSql(): string
    {
        $sql = "{$this->name} {$this->type}";

        if (!$this->nullable && !str_contains($this->type, 'PRIMARY KEY')) {
            $sql .= ' NOT NULL';
        }

        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        if ($this->hasDefault) {
            if ($this->isRawDefault) {
                $sql .= " DEFAULT {$this->default}";
            } elseif (is_null($this->default)) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($this->default)) {
                $sql .= ' DEFAULT ' . ($this->default ? '1' : '0');
            } elseif (is_numeric($this->default)) {
                $sql .= " DEFAULT {$this->default}";
            } else {
                $escaped = str_replace("'", "''", (string)$this->default);
                $sql .= " DEFAULT '{$escaped}'";
            }
        }

        return $sql;
    }
}

class QueryBuilder
{
    private string $table;
    private array $select = ['*'];
    private array $where = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    private array $groupBy = [];
    private array $having = [];
    private bool $distinct = false;
    private ?string $lockMode = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(string ...$columns): self
    {
        $this->select = $columns ?: ['*'];
        return $this;
    }

    public function addSelect(string ...$columns): self
    {
        if ($this->select === ['*']) {
            $this->select = [];
        }
        $this->select = array_merge($this->select, $columns);
        return $this;
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue = null, mixed $value = null): self
    {
        return $this->addWhere('AND', $column, $operatorOrValue, $value);
    }

    public function orWhere(string $column, mixed $operatorOrValue = null, mixed $value = null): self
    {
        return $this->addWhere('OR', $column, $operatorOrValue, $value);
    }

    private function addWhere(string $boolean, string $column, mixed $operatorOrValue, mixed $value): self
    {
        if ($value === null && $operatorOrValue !== null) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue ?? '=';
        }

        $this->where[] = [
            'boolean' => $boolean,
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->where[] = ['boolean' => 'AND', 'raw' => '1 = 0'];
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = ['boolean' => 'AND', 'raw' => "{$column} IN ({$placeholders})"];
        $this->bindings = array_merge($this->bindings, array_values($values));
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = ['boolean' => 'AND', 'raw' => "{$column} NOT IN ({$placeholders})"];
        $this->bindings = array_merge($this->bindings, array_values($values));
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where[] = ['boolean' => 'AND', 'raw' => "{$column} IS NULL"];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where[] = ['boolean' => 'AND', 'raw' => "{$column} IS NOT NULL"];
        return $this;
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->where[] = ['boolean' => 'AND', 'raw' => "{$column} BETWEEN ? AND ?"];
        $this->bindings[] = $min;
        $this->bindings[] = $max;
        return $this;
    }

    public function whereLike(string $column, string $pattern): self
    {
        $this->where[] = ['boolean' => 'AND', 'column' => $column, 'operator' => 'LIKE', 'value' => $pattern];
        $this->bindings[] = $pattern;
        return $this;
    }

    public function whereDate(string $column, string $operator, string $date): self
    {
        $this->where[] = ['boolean' => 'AND', 'raw' => "DATE({$column}) {$operator} ?"];
        $this->bindings[] = $date;
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->where[] = ['boolean' => 'AND', 'raw' => $sql];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "RIGHT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function crossJoin(string $table): self
    {
        $this->joins[] = "CROSS JOIN {$table}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    public function inRandomOrder(): self
    {
        $this->orderBy[] = 'RANDOM()';
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->having[] = $sql;
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    public function take(int $count): self
    {
        return $this->limit($count);
    }

    public function skip(int $count): self
    {
        return $this->offset($count);
    }

    public function forUpdate(): self
    {
        $this->lockMode = 'UPDATE';
        return $this;
    }

    public function sharedLock(): self
    {
        $this->lockMode = 'SHARED';
        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $countQuery = clone $this;
        $countQuery->select = ['*'];
        $countQuery->orderBy = [];
        $countQuery->limit = null;
        $countQuery->offset = null;
        $total = $countQuery->count();

        $lastPage = (int) ceil($total / $perPage);
        $items = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
            'to' => min($page * $perPage, $total),
            'has_more' => $page < $lastPage
        ];
    }

    public function chunk(int $size, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->paginate($page, $size);
            $items = $results['data'];

            if (empty($items)) {
                break;
            }

            if ($callback($items, $page) === false) {
                return false;
            }

            $page++;
        } while ($results['has_more']);

        return true;
    }

    public function get(): array
    {
        return DB::executeWithRetry(function() {
            $stmt = DB::pdo()->prepare($this->toSql());
            $stmt->execute($this->bindings);
            return $stmt->fetchAll();
        });
    }

    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function firstOrFail(): object
    {
        $result = $this->first();
        if (!$result) {
            throw new RecordNotFoundException("Record not found in table: {$this->table}");
        }
        return $result;
    }

    public function find(int|string $id, string $column = 'id'): ?object
    {
        return $this->where($column, $id)->first();
    }

    public function findOrFail(int|string $id, string $column = 'id'): object
    {
        return $this->where($column, $id)->firstOrFail();
    }

    public function value(string $column): mixed
    {
        $this->select($column);
        $result = $this->first();
        return $result?->$column;
    }

    public function pluck(string $column, string $key = null): array
    {
        $results = $this->get();
        $plucked = [];

        foreach ($results as $row) {
            if ($key !== null) {
                $plucked[$row->$key] = $row->$column;
            } else {
                $plucked[] = $row->$column;
            }
        }

        return $plucked;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function count(string $column = '*'): int
    {
        $query = clone $this;
        $query->select = ["COUNT({$column}) as aggregate"];
        $query->orderBy = [];
        $query->limit = null;
        $query->offset = null;
        return (int) ($query->first()?->aggregate ?? 0);
    }

    public function sum(string $column): float
    {
        $query = clone $this;
        $query->select = ["SUM({$column}) as aggregate"];
        $query->orderBy = [];
        return (float) ($query->first()?->aggregate ?? 0);
    }

    public function avg(string $column): float
    {
        $query = clone $this;
        $query->select = ["AVG({$column}) as aggregate"];
        $query->orderBy = [];
        return (float) ($query->first()?->aggregate ?? 0);
    }

    public function min(string $column): mixed
    {
        $query = clone $this;
        $query->select = ["MIN({$column}) as aggregate"];
        $query->orderBy = [];
        return $query->first()?->aggregate;
    }

    public function max(string $column): mixed
    {
        $query = clone $this;
        $query->select = ["MAX({$column}) as aggregate"];
        $query->orderBy = [];
        return $query->first()?->aggregate;
    }

    public function insert(array $data): bool
    {
        if (empty($data)) return false;

        if (!is_array(reset($data)) || !array_is_list($data)) {
            $data = [$data];
        }

        return DB::transaction(function() use ($data) {
            $columns = array_keys($data[0]);
            $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $allPlaceholders = implode(', ', array_fill(0, count($data), $placeholders));

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES {$allPlaceholders}";

            $bindings = [];
            foreach ($data as $row) {
                foreach ($columns as $col) {
                    $bindings[] = $row[$col] ?? null;
                }
            }

            $stmt = DB::pdo()->prepare($sql);
            return $stmt->execute($bindings);
        });
    }



    public function insertOrIgnore(array $data): bool
    {
        if (empty($data)) return false;

        if (!is_array(reset($data)) || !array_is_list($data)) {
            $data = [$data];
        }

        return DB::transaction(function() use ($data) {
            $columns = array_keys($data[0]);
            $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $allPlaceholders = implode(', ', array_fill(0, count($data), $placeholders));

            $sql = "INSERT OR IGNORE INTO {$this->table} (" . implode(', ', $columns) . ") VALUES {$allPlaceholders}";

            $bindings = [];
            foreach ($data as $row) {
                foreach ($columns as $col) {
                    $bindings[] = $row[$col] ?? null;
                }
            }

            $stmt = DB::pdo()->prepare($sql);
            return $stmt->execute($bindings);
        });
    }

    public function upsert(array $data, array $uniqueColumns, array $updateColumns = null): bool
    {
        if (empty($data)) return false;

        if (!is_array(reset($data)) || !array_is_list($data)) {
            $data = [$data];
        }

        return DB::transaction(function() use ($data, $uniqueColumns, $updateColumns) {
            $columns = array_keys($data[0]);
            $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $allPlaceholders = implode(', ', array_fill(0, count($data), $placeholders));

            $updateColumns = $updateColumns ?? array_diff($columns, $uniqueColumns);
            $updateParts = array_map(fn($col) => "{$col} = excluded.{$col}", $updateColumns);

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES {$allPlaceholders}";
            $sql .= " ON CONFLICT(" . implode(', ', $uniqueColumns) . ")";
            $sql .= " DO UPDATE SET " . implode(', ', $updateParts);

            $bindings = [];
            foreach ($data as $row) {
                foreach ($columns as $col) {
                    $bindings[] = $row[$col] ?? null;
                }
            }

            $stmt = DB::pdo()->prepare($sql);
            return $stmt->execute($bindings);
        });
    }

    public function insertGetId(array $data): int
    {
        return DB::transaction(function() use ($data) {
            $columns = array_keys($data);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";

            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute(array_values($data));

            return (int) DB::pdo()->lastInsertId();
        });
    }

    public function update(array $data): int
    {
        return DB::transaction(function() use ($data) {
            $sets = [];
            $bindings = [];

            foreach ($data as $column => $value) {
                $sets[] = "{$column} = ?";
                $bindings[] = $value;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
            $sql .= $this->buildWhereClause();

            $bindings = array_merge($bindings, $this->bindings);

            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        });
    }

    public function insertGetIdWithTimestamps(array $data): int
    {
        $now = Date::now()->toDbString();
        if (!isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }
        return $this->insertGetId($data);
    }

    public function updateWithTimestamp(array $data): int
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = Date::now()->toDbString();
        }
        return $this->update($data);
    }
        public function updateOrInsert(array $conditions, array $values): bool
    {
        $query = DB::table($this->table);
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        if ($query->exists()) {
            $query2 = DB::table($this->table);
            foreach ($conditions as $column => $value) {
                $query2->where($column, $value);
            }
            $query2->update($values);
        } else {
            DB::table($this->table)->insert(array_merge($conditions, $values));
        }

        return true;
    }

    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        return DB::transaction(function() use ($column, $amount, $extra) {
            $sets = ["{$column} = {$column} + ?"];
            $bindings = [$amount];

            foreach ($extra as $col => $value) {
                $sets[] = "{$col} = ?";
                $bindings[] = $value;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
            $sql .= $this->buildWhereClause();

            $bindings = array_merge($bindings, $this->bindings);

            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        });
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }

    public function delete(): int
    {
        return DB::transaction(function() {
            $sql = "DELETE FROM {$this->table}";
            $sql .= $this->buildWhereClause();

            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($this->bindings);
            return $stmt->rowCount();
        });
    }

    public function truncate(): bool
    {
        return DB::transaction(function() {
            DB::exec("DELETE FROM {$this->table}");
            DB::exec("DELETE FROM sqlite_sequence WHERE name = '{$this->table}'");
            return true;
        });
    }

    public function toSql(): string
    {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->select) . " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereClause();

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    private function buildWhereClause(): string
    {
        if (empty($this->where)) return '';

        $sql = ' WHERE ';
        $first = true;

        foreach ($this->where as $condition) {
            if (!$first) {
                $sql .= " {$condition['boolean']} ";
            }
            $first = false;

            if (isset($condition['raw'])) {
                $sql .= $condition['raw'];
            } else {
                $sql .= "{$condition['column']} {$condition['operator']} ?";
            }
        }

        return $sql;
    }

    public function dd(): never
    {
        header('Content-Type: text/plain');
        echo "SQL: " . $this->toSql() . "\n\n";
        echo "Bindings: " . print_r($this->bindings, true);
        exit;
    }
}

class RecordNotFoundException extends Exception {}

class Router
{
    private static array $routes = [];
    private static array $middleware = [];
    private static array $middlewareGroups = [];
    private static string $prefix = '';
    private static array $groupMiddleware = [];
    private static mixed $notFoundHandler = null;
    private static mixed $errorHandler = null;
    private static array $namedRoutes = [];

    public static function get(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('GET', $path, $handler);
    }

    public static function post(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('POST', $path, $handler);
    }

    public static function put(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('PUT', $path, $handler);
    }

    public static function patch(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('PATCH', $path, $handler);
    }

    public static function delete(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('DELETE', $path, $handler);
    }

    public static function options(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('OPTIONS', $path, $handler);
    }

    public static function any(string $path, callable|array|string $handler): Route
    {
        return self::addRoute('ANY', $path, $handler);
    }

    public static function match(array $methods, string $path, callable|array|string $handler): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = self::addRoute(strtoupper($method), $path, $handler);
        }
        return $route;
    }

    public static function resource(string $name, string $controller): void
    {
        self::get("/{$name}", "{$controller}@index")->name("{$name}.index");
        self::get("/{$name}/create", "{$controller}@create")->name("{$name}.create");
        self::post("/{$name}", "{$controller}@store")->name("{$name}.store");
        self::get("/{$name}/{id}", "{$controller}@show")->name("{$name}.show");
        self::get("/{$name}/{id}/edit", "{$controller}@edit")->name("{$name}.edit");
        self::put("/{$name}/{id}", "{$controller}@update")->name("{$name}.update");
        self::delete("/{$name}/{id}", "{$controller}@destroy")->name("{$name}.destroy");
    }

    public static function apiResource(string $name, string $controller): void
    {
        self::get("/{$name}", "{$controller}@index")->name("{$name}.index");
        self::post("/{$name}", "{$controller}@store")->name("{$name}.store");
        self::get("/{$name}/{id}", "{$controller}@show")->name("{$name}.show");
        self::put("/{$name}/{id}", "{$controller}@update")->name("{$name}.update");
        self::delete("/{$name}/{id}", "{$controller}@destroy")->name("{$name}.destroy");
    }

    private static function addRoute(string $method, string $path, callable|array|string $handler): Route
    {
        $fullPath = rtrim(self::$prefix . $path, '/') ?: '/';

        $route = new Route($method, $fullPath, $handler);
        $route->middleware(...self::$groupMiddleware);

        self::$routes[] = $route;
        return $route;
    }

    public static function group(array $options, callable $callback): void
    {
        $previousPrefix = self::$prefix;
        $previousMiddleware = self::$groupMiddleware;

        if (isset($options['prefix'])) {
            self::$prefix .= '/' . trim($options['prefix'], '/');
        }

        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $middleware);
        }

        $callback();

        self::$prefix = $previousPrefix;
        self::$groupMiddleware = $previousMiddleware;
    }

    public static function prefix(string $prefix): GroupBuilder
    {
        return new GroupBuilder(['prefix' => $prefix]);
    }

    public static function middleware(string ...$middleware): GroupBuilder
    {
        return new GroupBuilder(['middleware' => $middleware]);
    }

    public static function registerMiddleware(string $name, callable $handler): void
    {
        self::$middleware[$name] = $handler;
    }

    public static function middlewareGroup(string $name, array $middleware): void
    {
        self::$middlewareGroups[$name] = $middleware;
    }

    public static function fallback(callable|string $handler): void
    {
        self::$notFoundHandler = $handler;
    }

    public static function errorHandler(callable $handler): void
    {
        self::$errorHandler = $handler;
    }

    public static function registerRoute(string $name, Route $route): void
    {
        self::$namedRoutes[$name] = $route;
    }

    public static function route(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new Exception("Route not found: {$name}");
        }

        $path = self::$namedRoutes[$name]->getPath();

        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", (string)$value, $path);
        }

        return $path;
    }

    public static function dispatch(): mixed
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $uri = rawurldecode($uri);
            $uri = rtrim($uri, '/');
            $uri = $uri === '' ? '/' : $uri;

            if ($method === 'POST') {
                $override = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
                if ($override) {
                    $method = strtoupper($override);
                }
            }

            foreach (self::$routes as $route) {
                if ($route->matches($method, $uri)) {
                    return self::executeRoute($route, $uri);
                }
            }

            if (self::$notFoundHandler) {
                http_response_code(404);
                return self::callHandler(self::$notFoundHandler, new Request([]));
            }

            return Response::json(['error' => 'Not Found'], 404);

        } catch (ValidationException $e) {
            return Response::json(['errors' => $e->errors()], 422);
        } catch (RecordNotFoundException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            if (self::$errorHandler) {
                return call_user_func(self::$errorHandler, $e);
            }

            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return Response::json([
                'error' => $e->getMessage(),
                'code' => $code
            ], $code);
        }
    }
    private static function executeRoute(Route $route, string $uri): mixed
    {
        $params = $route->extractParams($uri);
        $request = new Request($params);

        $middlewareStack = [];
        foreach ($route->getMiddleware() as $name) {
            if (isset(self::$middlewareGroups[$name])) {
                $middlewareStack = array_merge($middlewareStack, self::$middlewareGroups[$name]);
            } else {
                $middlewareStack[] = $name;
            }
        }

        $next = fn($request) => self::callHandler($route->getHandler(), $request);

        foreach (array_reverse($middlewareStack) as $middlewareName) {
            if (isset(self::$middleware[$middlewareName])) {
                $middleware = self::$middleware[$middlewareName];
                $currentNext = $next;
                $next = fn($request) => $middleware($request, $currentNext);
            }
        }

        return $next($request);
    }

    private static function callHandler(callable|array|string $handler, Request $request): mixed
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);
            $handler = [new $class(), $method];
        }

        return call_user_func($handler, $request);
    }

    public static function routes(): array
    {
        return self::$routes;
    }

    public static function clear(): void
    {
        self::$routes = [];
        self::$namedRoutes = [];
        self::$prefix = '';
        self::$groupMiddleware = [];
    }
}

class Route
{
    private string $method;
    private string $path;
    private mixed $handler;
    private array $middleware = [];
    private ?string $name = null;
    private array $paramNames = [];
    private array $segments = [];

    public function __construct(string $method, string $path, mixed $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->parseSegments();
    }

    private function parseSegments(): void
    {
        $path = trim($this->path, '/');
        if ($path === '') {
            $this->segments = [];
            return;
        }

        $parts = explode('/', $path);
        foreach ($parts as $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $paramName = trim($part, '{}');
                $optional = str_ends_with($paramName, '?');
                $paramName = rtrim($paramName, '?');

                $this->segments[] = ['type' => 'param', 'name' => $paramName, 'optional' => $optional];
                $this->paramNames[] = $paramName;
            } else {
                $this->segments[] = ['type' => 'static', 'value' => $part];
            }
        }
    }

    public function matches(string $method, string $uri): bool
    {
        if ($this->method !== 'ANY' && $this->method !== $method) {
            return false;
        }

        $uri = trim($uri, '/');
        $uriParts = $uri === '' ? [] : explode('/', $uri);

        $segmentCount = count($this->segments);
        $uriCount = count($uriParts);

        if ($uriCount > $segmentCount) {
            return false;
        }

        $requiredSegments = 0;
        foreach ($this->segments as $segment) {
            if ($segment['type'] === 'static' || !($segment['optional'] ?? false)) {
                $requiredSegments++;
            }
        }

        if ($uriCount < $requiredSegments) {
            return false;
        }

        foreach ($this->segments as $index => $segment) {
            if (!isset($uriParts[$index])) {
                if ($segment['type'] === 'param' && ($segment['optional'] ?? false)) {
                    continue;
                }
                return false;
            }

            if ($segment['type'] === 'static' && $segment['value'] !== $uriParts[$index]) {
                return false;
            }
        }

        return true;
    }

    public function extractParams(string $uri): array
    {
        $params = [];
        $uri = trim($uri, '/');
        $uriParts = $uri === '' ? [] : explode('/', $uri);

        foreach ($this->segments as $index => $segment) {
            if ($segment['type'] === 'param') {
                $params[$segment['name']] = $uriParts[$index] ?? null;
            }
        }

        return $params;
    }

    public function middleware(string ...$middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        Router::registerRoute($name, $this);
        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}

class GroupBuilder
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function prefix(string $prefix): self
    {
        $existing = $this->options['prefix'] ?? '';
        $this->options['prefix'] = $existing . '/' . trim($prefix, '/');
        return $this;
    }

    public function middleware(string ...$middleware): self
    {
        $existing = $this->options['middleware'] ?? [];
        $this->options['middleware'] = array_merge($existing, $middleware);
        return $this;
    }

    public function group(callable $callback): void
    {
        Router::group($this->options, $callback);
    }
}

class Request
{
    private array $params;
    private array $query;
    private array $body;
    private array $files;
    private array $headers;
    private array $attributes = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->query = $_GET;
        $this->body = $this->parseBody();
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
    }

        private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (str_contains($contentType, 'application/json')) {
            $json = file_get_contents('php://input');
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($requestMethod === 'GET') {
            return [];
        }

        if (in_array($requestMethod, ['PUT', 'PATCH', 'DELETE'])) {
            if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                parse_str(file_get_contents('php://input'), $data);
                return $data;
            }
        }

        return $_POST;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function input(string $key = null, mixed $default = null): mixed
    {
        $all = array_merge($this->query, $this->body, $this->params);
        if ($key === null) return $all;
        return $all[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->input();
    }

    public function only(string ...$keys): array
    {
        return array_intersect_key($this->input(), array_flip($keys));
    }

    public function except(string ...$keys): array
    {
        return array_diff_key($this->input(), array_flip($keys));
    }

    public function has(string ...$keys): bool
    {
        $input = $this->input();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $input)) {
                return false;
            }
        }
        return true;
    }

    public function hasAny(string ...$keys): bool
    {
        $input = $this->input();
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                return true;
            }
        }
        return false;
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '' && $value !== [];
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file && $file['error'] === UPLOAD_ERR_OK;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $this->headers[str_replace('_', '-', $key)] ?? $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('AUTHORIZATION')
            ?? $this->header('Authorization')
            ?? $this->header('authorization');

        if ($auth && stripos($auth, 'Bearer ') === 0) {
            return substr($auth, 7);
        }
        return null;
    }
        public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $this->uri();
    }

    public function ip(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        return '127.0.0.1';
    }

    public function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443
            || ($this->header('X-FORWARDED-PROTO') === 'https');
    }

    public function isJson(): bool
    {
        return str_contains($this->header('CONTENT-TYPE', ''), 'application/json');
    }

    public function isAjax(): bool
    {
        return $this->header('X-REQUESTED-WITH') === 'XMLHttpRequest';
    }

    public function expectsJson(): bool
    {
        return $this->isAjax() || $this->isJson() || str_contains($this->header('ACCEPT', ''), 'application/json');
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function validate(array $rules): array
    {
        $validator = new Validator($this->input(), $rules);
        return $validator->validate();
    }
}

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $validated = [];

    private static array $customRules = [];
    private static array $customMessages = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function extend(string $rule, callable $callback, string $message = null): void
    {
        self::$customRules[$rule] = $callback;
        if ($message) {
            self::$customMessages[$rule] = $message;
        }
    }

    public function validate(): array
    {
        $this->check();

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return $this->validated;
    }

    private function check(): void
    {
        if (!empty($this->errors) || !empty($this->validated)) {
            return;
        }

        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->getValue($field);

            $isNullable = in_array('nullable', $rules);
            $isRequired = in_array('required', $rules);

            if (!$isRequired && !$isNullable && ($value === null || $value === '')) {
                continue;
            }

            if ($isNullable && ($value === null || $value === '')) {
                $this->validated[$field] = null;
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') continue;

                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramString] = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                $error = $this->validateRule($field, $value, $rule, $params);
                if ($error) {
                    $this->errors[$field][] = $error;
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function getValue(string $field): mixed
    {
        if (str_contains($field, '.')) {
            $keys = explode('.', $field);
            $value = $this->data;
            foreach ($keys as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) {
                    return null;
                }
                $value = $value[$key];
            }
            return $value;
        }
        return $this->data[$field] ?? null;
    }

    private function validateRule(string $field, mixed $value, string $rule, array $params): ?string
    {
        if (isset(self::$customRules[$rule])) {
            $callback = self::$customRules[$rule];
            if (!$callback($value, $params, $field, $this->data)) {
                return self::$customMessages[$rule] ?? "{$field} is invalid";
            }
            return null;
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    return "{$field} is required";
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$field} must be a valid email";
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$field} must be a valid URL";
                }
                break;

            case 'ip':
                if ($value && !filter_var($value, FILTER_VALIDATE_IP)) {
                    return "{$field} must be a valid IP address";
                }
                break;

            case 'min':
                $min = (float) $params[0];
                if (is_string($value) && mb_strlen($value) < $min) {
                    return "{$field} must be at least {$min} characters";
                }
                if (is_numeric($value) && $value < $min) {
                    return "{$field} must be at least {$min}";
                }
                if (is_array($value) && count($value) < $min) {
                    return "{$field} must have at least {$min} items";
                }
                break;

            case 'max':
                $max = (float) $params[0];
                if (is_string($value) && mb_strlen($value) > $max) {
                    return "{$field} must be at most {$max} characters";
                }
                if (is_numeric($value) && $value > $max) {
                    return "{$field} must be at most {$max}";
                }
                if (is_array($value) && count($value) > $max) {
                    return "{$field} must have at most {$max} items";
                }
                break;

            case 'between':
                $min = (float) $params[0];
                $max = (float) $params[1];
                $size = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
                if ($size < $min || $size > $max) {
                    return "{$field} must be between {$min} and {$max}";
                }
                break;

            case 'size':
                $size = (int) $params[0];
                $actual = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
                if ($actual != $size) {
                    return "{$field} must be exactly {$size}";
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    return "{$field} must be numeric";
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return "{$field} must be an integer";
                }
                break;

            case 'float':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_FLOAT)) {
                    return "{$field} must be a float";
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    return "{$field} must be a string";
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    return "{$field} must be an array";
                }
                break;

            case 'boolean':
                $valid = [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no'];
                if ($value !== null && !in_array($value, $valid, true)) {
                    return "{$field} must be a boolean";
                }
                break;

            case 'in':
                if ($value !== null && !in_array($value, $params, true)) {
                    return "{$field} must be one of: " . implode(', ', $params);
                }
                break;

            case 'not_in':
                if ($value !== null && in_array($value, $params, true)) {
                    return "{$field} must not be one of: " . implode(', ', $params);
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;
                if ($value !== $confirmValue) {
                    return "{$field} confirmation does not match";
                }
                break;

            case 'same':
                $otherField = $params[0];
                $otherValue = $this->data[$otherField] ?? null;
                if ($value !== $otherValue) {
                    return "{$field} must match {$otherField}";
                }
                break;

            case 'different':
                $otherField = $params[0];
                $otherValue = $this->data[$otherField] ?? null;
                if ($value === $otherValue) {
                    return "{$field} must be different from {$otherField}";
                }
                break;

            case 'unique':
                $table = preg_replace('/[^a-zA-Z0-9_]/', '', $params[0]);
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $params[1] ?? $field);
                $exceptId = isset($params[2]) ? (int)$params[2] : null;

                $query = DB::table($table)->where($column, $value);
                if ($exceptId !== null) {
                    $query->where('id', '!=', $exceptId);
                }
                if ($query->exists()) {
                    return "{$field} already exists";
                }
                break;

            case 'exists':
                $table = preg_replace('/[^a-zA-Z0-9_]/', '', $params[0]);
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $params[1] ?? $field);
                if ($value && !DB::table($table)->where($column, $value)->exists()) {
                    return "{$field} does not exist";
                }
                break;
            case 'date':
                if ($value && strtotime($value) === false) {
                    return "{$field} must be a valid date";
                }
                break;

            case 'date_format':
                $format = $params[0];
                $d = DateTime::createFromFormat($format, $value);
                if (!$d || $d->format($format) !== $value) {
                    return "{$field} must match format {$format}";
                }
                break;

            case 'before':
                $date = $params[0];
                if (strtotime($value) >= strtotime($date)) {
                    return "{$field} must be before {$date}";
                }
                break;

            case 'after':
                $date = $params[0];
                if (strtotime($value) <= strtotime($date)) {
                    return "{$field} must be after {$date}";
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha($value)) {
                    return "{$field} must only contain letters";
                }
                break;

            case 'alpha_num':
                if ($value && !ctype_alnum($value)) {
                    return "{$field} must only contain letters and numbers";
                }
                break;

            case 'alpha_dash':
                if ($value && !preg_match('/^[\pL\pM\pN_-]+$/u', $value)) {
                    return "{$field} must only contain letters, numbers, dashes and underscores";
                }
                break;

            case 'regex':
                $pattern = $params[0];
                if ($value && !preg_match($pattern, $value)) {
                    return "{$field} format is invalid";
                }
                break;

            case 'json':
                if ($value && json_decode($value) === null && json_last_error() !== JSON_ERROR_NONE) {
                    return "{$field} must be valid JSON";
                }
                break;

            case 'uuid':
                $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
                if ($value && !preg_match($pattern, $value)) {
                    return "{$field} must be a valid UUID";
                }
                break;
        }

        return null;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        $this->check();
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        $this->check();
        return empty($this->errors);
    }
}

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }
}

class Response
{
    public static function json(mixed $data, int $status = 200, array $headers = []): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '';
    }

    public static function html(string $content, int $status = 200, array $headers = []): string
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $content;
        return '';
    }

    public static function text(string $content, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
        return '';
    }

    public static function xml(string $content, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=utf-8');
        echo $content;
        return '';
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    public static function download(string $path, string $name = null, array $headers = []): never
    {
        if (!file_exists($path)) {
            throw new Exception("File not found: {$path}");
        }

        $name = $name ?? basename($path);
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"{$name}\"");
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        readfile($path);
        exit;
    }

    public static function file(string $path, array $headers = []): never
    {
        if (!file_exists($path)) {
            throw new Exception("File not found: {$path}");
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        header("Content-Type: {$mime}");
        header('Content-Length: ' . filesize($path));

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        readfile($path);
        exit;
    }

    public static function stream(callable $callback, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: application/octet-stream');
        header('Transfer-Encoding: chunked');
        header('X-Accel-Buffering: no');

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        ob_end_flush();
        $callback();
        exit;
    }

    public static function noContent(): string
    {
        http_response_code(204);
        return '';
    }

    public static function created(mixed $data = null, string $location = null): string
    {
        if ($location) {
            header("Location: {$location}");
        }
        return self::json($data ?? ['message' => 'Created'], 201);
    }

    public static function accepted(mixed $data = null): string
    {
        return self::json($data ?? ['message' => 'Accepted'], 202);
    }

    public static function error(string $message, int $status = 500, array $extra = []): string
    {
        $response = array_merge(['error' => $message], $extra);
        return self::json($response, $status);
    }

    public static function success(string $message, mixed $data = null, int $status = 200): string
    {
        $response = ['message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return self::json($response, $status);
    }

    public static function paginated(array $pagination): string
    {
        return self::json($pagination);
    }

      public static function cors(array $options = []): void
    {
        if (headers_sent()) {
            return;
        }

        $defaults = [
            'origin' => '*',
            'methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'headers' => 'Content-Type, Authorization, X-Requested-With',
            'credentials' => false,
            'max_age' => 86400
        ];

        $options = array_merge($defaults, $options);

        header("Access-Control-Allow-Origin: {$options['origin']}");
        header("Access-Control-Allow-Methods: {$options['methods']}");
        header("Access-Control-Allow-Headers: {$options['headers']}");
        header("Access-Control-Max-Age: {$options['max_age']}");

        if ($options['credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

function db(): PDO
{
    return DB::pdo();
}

function table(string $name): QueryBuilder
{
    return DB::table($name);
}

function schema(): SchemaBuilder
{
    return DB::schema();
}

function request(): Request
{
    static $request;
    return $request ??= new Request();
}

function json(mixed $data, int $status = 200): string
{
    return Response::json($data, $status);
}

function redirect(string $url, int $status = 302): never
{
    Response::redirect($url, $status);
}

function back(): never
{
    Response::back();
}

function abort(int $code, string $message = ''): never
{
    $messages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable'
    ];

    $message = $message ?: ($messages[$code] ?? 'Error');
    Response::json(['error' => $message], $code);
    exit;
}

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;

    if (!$loaded && file_exists('.env')) {
        $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_contains($line, '=')) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
        $loaded = true;
    }

    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function config(string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = file_exists('config.php') ? include 'config.php' : [];
    }

    if ($key === null) return $config;

    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value;
}

function cache(string $key = null, mixed $value = null, int $ttl = 3600): mixed
{
    static $cacheDir = null;
    $cacheDir ??= sys_get_temp_dir() . '/php_cache';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    if ($key === null) {
        return new class($cacheDir) {
            public function __construct(private string $dir) {}

            public function flush(): void {
                $files = glob($this->dir . '/*.cache');
                foreach ($files as $file) unlink($file);
            }

            public function forget(string $key): void {
                $file = $this->dir . '/' . md5($key) . '.cache';
                if (file_exists($file)) unlink($file);
            }
        };
    }

    $file = $cacheDir . '/' . md5($key) . '.cache';

    if ($value === null) {
        if (!file_exists($file)) return null;

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        return $data['value'];
    }

    $data = ['value' => $value, 'expires' => time() + $ttl];
    file_put_contents($file, serialize($data), LOCK_EX);
    return $value;
}

function session(string $key = null, mixed $value = null): mixed
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($key === null) {
        return $_SESSION;
    }

    if ($value === null) {
        return $_SESSION[$key] ?? null;
    }

    $_SESSION[$key] = $value;
    return $value;
}

function flash(string $key, mixed $value = null): mixed
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($value === null) {
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    $_SESSION['_flash'][$key] = $value;
    return $value;
}

function old(string $key, mixed $default = null): mixed
{
    return flash('_old')[$key] ?? $default;
}

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function csrf_verify(string $token = null): bool
{
    $token ??= $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrf_token(), $token);
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
}


function uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function slug(string $text, string $divider = '-'): string
{
    // replace non letter or digits by divider
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, $divider);

    // remove duplicate divider
    $text = preg_replace('~-+~', $divider, $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function collect(array $items = []): Collection
{
    return new Collection($items);
}

function dump(...$vars): void
{
    echo '<pre style="background:#1e1e1e;color:#dcdcdc;padding:15px;margin:10px;border-radius:5px;overflow:auto;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
}

function dd(...$vars): never
{
    dump(...$vars);
    exit(1);
}

function logger(): Logger
{
    static $logger;
    return $logger ??= new Logger();
}

function retry(int $times, callable $callback, int $sleepMs = 0, callable $when = null): mixed
{
    $attempts = 0;
    $lastException = null;

    while ($attempts < $times) {
        try {
            return $callback($attempts);
        } catch (Throwable $e) {
            $lastException = $e;
            $attempts++;

            if ($when && !$when($e)) {
                throw $e;
            }

            if ($attempts < $times && $sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
    }

    throw $lastException;
}

function rescue(callable $callback, mixed $default = null, callable $report = null): mixed
{
    try {
        return $callback();
    } catch (Throwable $e) {
        if ($report) {
            $report($e);
        }
        return $default instanceof Closure ? $default($e) : $default;
    }
}

function tap(mixed $value, callable $callback): mixed
{
    $callback($value);
    return $value;
}

function value(mixed $value, ...$args): mixed
{
    return $value instanceof Closure ? $value(...$args) : $value;
}

function with(mixed $value, callable $callback = null): mixed
{
    return $callback ? $callback($value) : $value;
}

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function first(callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_first($this->items)] ?? $default;
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function last(callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_last($this->items)] ?? $default;
        }

        return $this->reverse()->first($callback, $default);
    }

    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function map(callable $callback): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        return new self($result);
    }

    public function filter(callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function reject(callable $callback): self
    {
        return $this->filter(fn($value, $key) => !$callback($value, $key));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function pluck(string $value, string $key = null): self
    {
        $result = [];
        foreach ($this->items as $item) {
            $item = (array) $item;
            if ($key !== null) {
                $result[$item[$key]] = $item[$value];
            } else {
                $result[] = $item[$value];
            }
        }
        return new self($result);
    }

    public function where(string $key, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function($item) use ($key, $operator, $value) {
            $item = (array) $item;
            $itemValue = $item[$key] ?? null;

            return match($operator) {
                '=' => $itemValue == $value,
                '==' => $itemValue == $value,
                '===' => $itemValue === $value,
                '!=' => $itemValue != $value,
                '<>' => $itemValue != $value,
                '>' => $itemValue > $value,
                '>=' => $itemValue >= $value,
                '<' => $itemValue < $value,
                '<=' => $itemValue <= $value,
                default => false
            };
        });
    }

    public function whereIn(string $key, array $values): self
    {
        return $this->filter(fn($item) => in_array(((array)$item)[$key] ?? null, $values));
    }

    public function whereNotIn(string $key, array $values): self
    {
        return $this->filter(fn($item) => !in_array(((array)$item)[$key] ?? null, $values));
    }

    public function whereNull(string $key): self
    {
        return $this->filter(fn($item) => (((array)$item)[$key] ?? null) === null);
    }

    public function whereNotNull(string $key): self
    {
        return $this->filter(fn($item) => (((array)$item)[$key] ?? null) !== null);
    }

    public function sortBy(string|callable $key, int $options = SORT_REGULAR, bool $descending = false): self
    {
        $items = $this->items;

        $callback = is_callable($key) ? $key : fn($item) => ((array)$item)[$key] ?? null;

        uasort($items, function($a, $b) use ($callback, $descending) {
            $aValue = $callback($a);
            $bValue = $callback($b);

            $result = $aValue <=> $bValue;
            return $descending ? -$result : $result;
        });

        return new self($items);
    }

    public function sortByDesc(string|callable $key, int $options = SORT_REGULAR): self
    {
        return $this->sortBy($key, $options, true);
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->items, true));
    }

    public function values(): self
    {
        return new self(array_values($this->items));
    }

    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    public function unique(string $key = null): self
    {
        if ($key === null) {
            return new self(array_unique($this->items, SORT_REGULAR));
        }

        $seen = [];
        return $this->filter(function($item) use ($key, &$seen) {
            $value = ((array)$item)[$key] ?? null;
            if (in_array($value, $seen, true)) {
                return false;
            }
            $seen[] = $value;
            return true;
        });
    }

    public function chunk(int $size): self
    {
        return new self(array_chunk($this->items, $size, true));
    }

    public function take(int $limit): self
    {
        return new self(array_slice($this->items, 0, $limit, true));
    }

    public function skip(int $count): self
    {
        return new self(array_slice($this->items, $count, null, true));
    }

    public function slice(int $offset, int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length, true));
    }

    public function merge(array|self $items): self
    {
        $items = $items instanceof self ? $items->all() : $items;
        return new self(array_merge($this->items, $items));
    }

    public function combine(array|self $values): self
    {
        $values = $values instanceof self ? $values->all() : $values;
        return new self(array_combine($this->items, $values));
    }

    public function flip(): self
    {
        return new self(array_flip($this->items));
    }

    public function groupBy(string|callable $key): self
    {
        $callback = is_callable($key) ? $key : fn($item) => ((array)$item)[$key] ?? null;

        $result = [];
        foreach ($this->items as $item) {
            $groupKey = $callback($item);
            $result[$groupKey][] = $item;
        }

        return new self(array_map(fn($group) => new self($group), $result));
    }

    public function keyBy(string|callable $key): self
    {
        $callback = is_callable($key) ? $key : fn($item) => ((array)$item)[$key] ?? null;

        $result = [];
        foreach ($this->items as $item) {
            $result[$callback($item)] = $item;
        }

        return new self($result);
    }

    public function sum(string|callable $key = null): int|float
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        $callback = is_callable($key) ? $key : fn($item) => ((array)$item)[$key] ?? 0;
        return $this->reduce(fn($carry, $item) => $carry + $callback($item), 0);
    }

    public function avg(string|callable $key = null): int|float|null
    {
        $count = $this->count();
        if ($count === 0) {
            return null;
        }
        return $this->sum($key) / $count;
    }
    public function min(string|callable $key = null): mixed
    {
        if ($key === null) {
            return min($this->items);
        }

        $callback = is_callable($key) ? $key : fn($item) => ((array)$item)[$key] ?? null;
        return $this->map($callback)->min();
    }

    public function max(string|callable $key = null): mixed
    {
        if ($key === null) {
            return max($this->items);
        }

        $callback = is_callable($key) ? $key : fn($item) => ((array)$item)[$key] ?? null;
        return $this->map($callback)->max();
    }

    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if (is_callable($key)) {
                return $this->first($key) !== null;
            }
            return in_array($key, $this->items, true);
        }

        return $this->where($key, $operator, $value)->isNotEmpty();
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return array_map(fn($item) => $item instanceof self ? $item->toArray() : $item, $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function push(...$values): self
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        return $this;
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    public function prepend(mixed $value, mixed $key = null): self
    {
        if ($key !== null) {
            $this->items = [$key => $value] + $this->items;
        } else {
            array_unshift($this->items, $value);
        }
        return $this;
    }

    public function pull(mixed $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        unset($this->items[$key]);
        return $value;
    }

    public function put(mixed $key, mixed $value): self
    {
        $this->items[$key] = $value;
        return $this;
    }

    public function forget(mixed $keys): self
    {
        foreach ((array) $keys as $key) {
            unset($this->items[$key]);
        }
        return $this;
    }

    public function implode(string $glue, string $key = null): string
    {
        if ($key !== null) {
            return implode($glue, $this->pluck($key)->all());
        }
        return implode($glue, $this->items);
    }

    public function random(int $count = null): mixed
    {
        $keys = array_rand($this->items, $count ?? 1);

        if ($count === null) {
            return $this->items[$keys];
        }

        return new self(array_intersect_key($this->items, array_flip((array) $keys)));
    }

    public function shuffle(): self
    {
        $items = $this->items;
        shuffle($items);
        return new self($items);
    }

    public function dd(): never
    {
        dd($this->toArray());
    }

    public function dump(): self
    {
        dump($this->toArray());
        return $this;
    }
}

class Logger
{
    private string $path;
    private string $level;
    private array $levels = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7
    ];

    public function __construct(string $path = null, string $level = 'debug')
    {
        $this->path = $path ?? 'logs/app.log';
        $this->level = strtolower($level);

        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        
        if (!isset($this->levels[$level]) || !isset($this->levels[$this->level])) {
            return;
        }
        
        // Only log if the message level is >= the configured minimum level
        if ($this->levels[$level] < $this->levels[$this->level]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $line = "[{$timestamp}] {$levelUpper}: {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }
}
class RateLimiter
{
    private string $cacheDir;

    public function __construct(string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/rate_limit';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $file = $this->cacheDir . '/' . md5($key) . '.limit';

        $data = $this->getData($file);
        $now = time();

        $data['attempts'] = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => $timestamp > ($now - $decaySeconds)
        );

        if (count($data['attempts']) >= $maxAttempts) {
            return false;
        }

        $data['attempts'][] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);

        return true;
    }

    public function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $file = $this->cacheDir . '/' . md5($key) . '.limit';
        $data = $this->getData($file);
        $now = time();

        $recentAttempts = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => $timestamp > ($now - $decaySeconds)
        );

        return count($recentAttempts) >= $maxAttempts;
    }

    public function remainingAttempts(string $key, int $maxAttempts, int $decaySeconds): int
    {
        $file = $this->cacheDir . '/' . md5($key) . '.limit';
        $data = $this->getData($file);
        $now = time();

        $recentAttempts = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => $timestamp > ($now - $decaySeconds)
        );

        return max(0, $maxAttempts - count($recentAttempts));
    }

    public function retriesIn(string $key, int $decaySeconds): int
    {
        $file = $this->cacheDir . '/' . md5($key) . '.limit';
        $data = $this->getData($file);

        if (empty($data['attempts'])) {
            return 0;
        }

        $oldestAttempt = min($data['attempts']);
        $retryAt = $oldestAttempt + $decaySeconds;

        return max(0, $retryAt - time());
    }

    public function clear(string $key): void
    {
        $file = $this->cacheDir . '/' . md5($key) . '.limit';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function getData(string $file): array
    {
        if (!file_exists($file)) {
            return ['attempts' => []];
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? ['attempts' => []];
    }
}

function rateLimit(): RateLimiter
{
    static $limiter;
    return $limiter ??= new RateLimiter();
}

class Lock
{
    private static string $lockDir;
    private static array $handles = [];

    public static function init(string $dir = null): void
    {
        self::$lockDir = $dir ?? sys_get_temp_dir() . '/locks';
        if (!is_dir(self::$lockDir)) {
            mkdir(self::$lockDir, 0755, true);
        }
    }

    public static function acquire(string $name, int $timeout = 10): bool
    {
        self::init();

        $file = self::$lockDir . '/' . md5($name) . '.lock';
        $start = time();

        while (true) {
            $fp = fopen($file, 'c');

            if ($fp && flock($fp, LOCK_EX | LOCK_NB)) {
                ftruncate($fp, 0);
                fwrite($fp, (string) getmypid());
                fflush($fp);
                // Store the handle so we can release it later
                self::$handles[$name] = $fp;
                return true;
            }

            if ($fp) {
                fclose($fp);
            }

            if (time() - $start >= $timeout) {
                return false;
            }

            usleep(50000);
        }
    }

    public static function release(string $name): void
    {
        if (isset(self::$handles[$name])) {
            $fp = self::$handles[$name];
            flock($fp, LOCK_UN);
            fclose($fp);
            unset(self::$handles[$name]);
        }

        self::init();
        $file = self::$lockDir . '/' . md5($name) . '.lock';
        @unlink($file);
    }

    public static function run(string $name, callable $callback, int $timeout = 10): mixed
    {
        if (!self::acquire($name, $timeout)) {
            throw new Exception("Could not acquire lock: {$name}");
        }

        try {
            return $callback();
        } finally {
            self::release($name);
        }
    }

    public static function isLocked(string $name): bool
    {
        self::init();

        $file = self::$lockDir . '/' . md5($name) . '.lock';

        if (!file_exists($file)) {
            return false;
        }

        $fp = fopen($file, 'c');
        if (!$fp) {
            return false;
        }

        $locked = !flock($fp, LOCK_EX | LOCK_NB);

        if (!$locked) {
            flock($fp, LOCK_UN);
        }

        fclose($fp);
        return $locked;
    }
}
class Queue
{
    private string $table;

    public function __construct(string $table = 'jobs')
    {
        $this->table = $table;
    }

    public function createTable(): void
    {
        schema()->create($this->table, function(Blueprint $t) {
            $t->id();
            $t->string('queue')->default('default');
            $t->text('payload');
            $t->integer('attempts')->default(0);
            $t->datetime('available_at');
            $t->datetime('reserved_at')->nullable();
            $t->datetime('created_at');
            $t->index('queue', 'available_at');
        });
    }

    public function push(string $job, array $data = [], string $queue = 'default', int $delay = 0): int
    {
        return DB::table($this->table)->insertGetId([
            'queue' => $queue,
            'payload' => json_encode(['job' => $job, 'data' => $data]),
            'available_at' => date('Y-m-d H:i:s', time() + $delay),
            'created_at' => Date::now()->toDbString()
        ]);
    }

    public function later(int $delay, string $job, array $data = [], string $queue = 'default'): int
    {
        return $this->push($job, $data, $queue, $delay);
    }

    public function pop(string $queue = 'default'): ?object
    {
        return DB::transaction(function() use ($queue) {
            $now = Date::now()->toDbString();

            $job = DB::table($this->table)
                ->where('queue', $queue)
                ->whereNull('reserved_at')
                ->where('available_at', '<=', $now)
                ->orderBy('id')
                ->first();

            if (!$job) return null;

            DB::table($this->table)
                ->where('id', $job->id)
                ->update([
                    'reserved_at' => $now,
                    'attempts' => $job->attempts + 1
                ]);

            $job->attempts++;
            $job->reserved_at = $now;

            return $job;
        });
    }

    public function delete(int $id): void
    {
        DB::table($this->table)->where('id', $id)->delete();
    }

    public function release(int $id, int $delay = 0): void
    {
        DB::table($this->table)->where('id', $id)->update([
            'reserved_at' => null,
            'available_at' => date('Y-m-d H:i:s', time() + $delay)
        ]);
    }

    public function failed(int $id): void
    {
        $job = DB::table($this->table)->find($id);

        if ($job) {
            DB::table('failed_jobs')->insert([
                'queue' => $job->queue,
                'payload' => $job->payload,
                'exception' => '',
                'failed_at' => Date::now()->toDbString()
            ]);

            $this->delete($id);
        }
    }

    public function work(string $queue = 'default', int $maxJobs = 0, int $sleep = 3): void
    {
        $processed = 0;

        while (true) {
            $job = $this->pop($queue);

            if ($job) {
                try {
                    $payload = json_decode($job->payload, true);
                    $handler = $payload['job'];
                    $data = $payload['data'];

                    if (is_callable($handler)) {
                        $handler($data);
                    } elseif (class_exists($handler)) {
                        (new $handler())->handle($data);
                    }

                    $this->delete($job->id);
                    $processed++;

                    if ($maxJobs > 0 && $processed >= $maxJobs) {
                        break;
                    }
                } catch (Throwable $e) {
                    if ($job->attempts >= 3) {
                        $this->failed($job->id);
                    } else {
                        $this->release($job->id, 60 * $job->attempts);
                    }

                    logger()->error('Job failed', [
                        'job' => $job->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                sleep($sleep);
            }
        }
    }

        public function size(string $queue = 'default'): int
    {
        $now = Date::now()->toDbString();
        return DB::table($this->table)
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $now)
            ->count();
    }

    public function clear(string $queue = 'default'): int
    {
        return DB::table($this->table)->where('queue', $queue)->delete();
    }
}
function queue(): Queue
{
    static $queue;
    return $queue ??= new Queue();
}

register_shutdown_function(function() {
    DB::close();
});
