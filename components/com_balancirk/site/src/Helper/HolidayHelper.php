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

/**
 * Load and match closed days from `#__balancirk_holidays`.
 *
 * Each row is an inclusive range (`startDate` .. `endDate`). Attendance and
 * generated lesson dates skip every calendar day inside those ranges. Fill
 * the table in the admin holiday list; do not invent holiday dates in PHP.
 *
 * @since  1.3.24
 */
class HolidayHelper
{
    /**
     * Database table with holiday ranges.
     *
     * @var    string
     * @since  1.3.24
     */
    public const TABLE = '#__balancirk_holidays';

    /**
     * Load holiday ranges that overlap [from, to], as ISO date pairs.
     *
     * @param   object  $db       Joomla database driver.
     * @param   string  $fromIso  Period start in Y-m-d.
     * @param   string  $toIso    Period end in Y-m-d.
     *
     * @return  array<int, array{start: string, end: string}>
     *
     * @since   1.3.24
     */
    public static function loadOverlappingRanges(object $db, string $fromIso, string $toIso): array
    {
        if ($fromIso === '' || $toIso === '') {
            return [];
        }

        try {
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('startDate', 'start'),
                    $db->quoteName('endDate', 'end'),
                ])
                ->from($db->quoteName(self::TABLE))
                ->where($db->quoteName('startDate') . ' <= ' . $db->quote($toIso))
                ->where($db->quoteName('endDate') . ' >= ' . $db->quote($fromIso));

            $rows = $db->setQuery($query)->loadAssocList() ?: [];
            $ranges = [];

            foreach ($rows as $row) {
                $start = self::isoDate($row['start'] ?? null);
                $end = self::isoDate($row['end'] ?? null);

                if ($start !== '' && $end !== '') {
                    $ranges[] = ['start' => $start, 'end' => $end];
                }
            }

            return $ranges;
        } catch (\Throwable $exception) {
            return [];
        }
    }

    /**
     * Whether an ISO date falls inside any inclusive holiday range.
     *
     * @param   string  $iso     Date in Y-m-d.
     * @param   array   $ranges  Ranges with start/end keys.
     *
     * @return  bool
     *
     * @since   1.3.24
     */
    public static function containsIsoDate(string $iso, array $ranges): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)) {
            return false;
        }

        foreach ($ranges as $range) {
            $start = is_array($range) ? self::isoDate($range['start'] ?? null) : '';
            $end = is_array($range) ? self::isoDate($range['end'] ?? null) : '';

            if ($start !== '' && $end !== '' && $iso >= $start && $iso <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise a stored date value to Y-m-d.
     *
     * @param   mixed  $value  Column value.
     *
     * @return  string
     *
     * @since   1.3.24
     */
    private static function isoDate(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }

        return preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches) === 1 ? $matches[1] : '';
    }
}
