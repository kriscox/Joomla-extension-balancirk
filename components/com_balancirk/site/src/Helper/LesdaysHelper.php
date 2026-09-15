<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use DateTimeInterface;

/**
 * Encode and decode the lesson-days bitmask.
 *
 * Days are stored as a single integer. The mapping matches the
 * `#__balancirk_lessons.lesdays` column comment:
 *
 * 64 = Monday, 32 = Tuesday, 16 = Wednesday, 8 = Thursday,
 * 4 = Friday, 2 = Saturday, 1 = Sunday.
 *
 * @since  1.3.22
 */
class LesdaysHelper
{
    /**
     * Weekday name to bit value, Monday first.
     *
     * @var    array<string, int>
     * @since  1.3.22
     */
    public const DAYS = [
        'Monday' => 64,
        'Tuesday' => 32,
        'Wednesday' => 16,
        'Thursday' => 8,
        'Friday' => 4,
        'Saturday' => 2,
        'Sunday' => 1,
    ];

    /**
     * Bit values indexed by JavaScript Date.getDay() (0 = Sunday).
     *
     * @var    int[]
     * @since  1.3.22
     */
    public const JS_GETDAY_BITS = [1, 64, 32, 16, 8, 4, 2];

    /**
     * Decode a bitmask into weekday flags keyed by English day name.
     *
     * @param   int  $mask  Stored lesdays value.
     *
     * @return  array<string, int>
     *
     * @since   1.3.22
     */
    public static function toWeekdays(int $mask): array
    {
        $weekdays = [];

        foreach (self::DAYS as $day => $bit) {
            $weekdays[$day] = (($mask & $bit) === $bit) ? 1 : 0;
        }

        return $weekdays;
    }

    /**
     * Selected bit values for a Joomla checkboxes field.
     *
     * Returns a list of strings such as ["64", "4"] so the form can tick
     * every selected day. A comma-separated string with spaces does not
     * match option values and only the first day would appear checked.
     *
     * @param   int  $mask  Stored lesdays value.
     *
     * @return  string[]
     *
     * @since   1.3.22
     */
    public static function toFormValues(int $mask): array
    {
        $values = [];

        foreach (self::DAYS as $bit) {
            if (($mask & $bit) === $bit) {
                $values[] = (string) $bit;
            }
        }

        return $values;
    }

    /**
     * Combine form checkbox values into a stored bitmask.
     *
     * Accepts an array of bit values or a comma-separated string. Unknown
     * values are ignored. Bits are OR-ed so a day cannot be counted twice.
     *
     * @param   mixed  $values  Checkbox values from the lesson form.
     *
     * @return  int
     *
     * @since   1.3.22
     */
    public static function fromFormValues(mixed $values): int
    {
        if (is_string($values)) {
            $values = preg_split('/\s*,\s*/', $values, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (!is_array($values)) {
            return 0;
        }

        $mask = 0;
        $validBits = array_values(self::DAYS);

        foreach ($values as $value) {
            $bit = (int) $value;

            if (in_array($bit, $validBits, true)) {
                $mask |= $bit;
            }
        }

        return $mask;
    }

    /**
     * Whether a weekday-flag array has at least one selected day.
     *
     * @param   array<string, int>  $weekdays  Flags from toWeekdays().
     *
     * @return  bool
     *
     * @since   1.3.22
     */
    public static function hasConfiguredDays(array $weekdays): bool
    {
        return in_array(1, $weekdays, true);
    }

    /**
     * Whether a calendar date falls on a selected lesson weekday.
     *
     * @param   DateTimeInterface  $date  Calendar date.
     * @param   int                $mask  Stored lesdays value.
     *
     * @return  bool
     *
     * @since   1.3.22
     */
    public static function matchesDate(DateTimeInterface $date, int $mask): bool
    {
        $bit = self::DAYS[$date->format('l')] ?? 0;

        return $bit > 0 && ($mask & $bit) === $bit;
    }
}
