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

use Joomla\CMS\Component\ComponentHelper;

/**
 * Helper methods for school year calculations.
 *
 * @since  1.3.8
 */
class SchoolYearHelper
{
    /**
     * Default number of months to subtract before taking the calendar year.
     *
     * @var    int
     * @since  1.3.8
     */
    public const DEFAULT_OFFSET_MONTHS = 6;

    /**
     * Filter value that disables the year restriction ("all school years").
     *
     * @var    string
     * @since  1.3.22
     */
    public const ALL_YEARS_FILTER = '*';

    /**
     * Return the configured current school year.
     *
     * @param   string|null  $date  Reference date in Y-m-d format.
     *
     * @return  int
     *
     * @since   1.3.8
     */
    public static function getCurrentSchoolYear(?string $date = null): int
    {
        return self::calculateSchoolYear($date, self::getOffsetMonths());
    }

    /**
     * Calculate the school year for a date and offset.
     *
     * @param   string|null  $date          Reference date in Y-m-d format.
     * @param   int          $offsetMonths  Number of months to subtract.
     *
     * @return  int
     *
     * @since   1.3.8
     */
    public static function calculateSchoolYear(?string $date, int $offsetMonths): int
    {
        $date = $date ?: date('Y-m-d');
        $offsetMonths = max(0, $offsetMonths);

        return (int) date('Y', strtotime($date . ' - ' . $offsetMonths . ' months'));
    }

    /**
     * Return the configured school year offset in months.
     *
     * @return  int
     *
     * @since   1.3.8
     */
    public static function getOffsetMonths(): int
    {
        $params = ComponentHelper::getParams('com_balancirk');

        return max(0, (int) $params->get('school_year_offset_months', self::DEFAULT_OFFSET_MONTHS));
    }

    /**
     * Resolve a list-filter year to the value used in SQL.
     *
     * Empty or null defaults to the current school year. The all-years
     * token means no year restriction.
     *
     * @param   mixed        $year          Submitted or stored filter value.
     * @param   string|null  $date          Reference date in Y-m-d format.
     * @param   int|null     $offsetMonths  Offset override; null uses component config.
     *
     * @return  string|null  School year to filter on, or null for all years.
     *
     * @since   1.3.22
     */
    public static function resolveListFilterYear(mixed $year, ?string $date = null, ?int $offsetMonths = null): ?string
    {
        $year = trim((string) ($year ?? ''));

        if ($year === self::ALL_YEARS_FILTER) {
            return null;
        }

        if ($year === '') {
            $offsetMonths = $offsetMonths ?? self::getOffsetMonths();

            return (string) self::calculateSchoolYear($date, $offsetMonths);
        }

        return $year;
    }

    /**
     * Add a year to a descending year list when it is not already present.
     *
     * @param   array<int|string>  $years  Existing years.
     * @param   int|string         $year   Year that must appear in the list.
     *
     * @return  array<int|string>
     *
     * @since   1.3.22
     */
    public static function ensureYearInList(array $years, int|string $year): array
    {
        $year = (string) $year;

        foreach ($years as $existing) {
            if ((string) $existing === $year) {
                return array_values($years);
            }
        }

        array_unshift($years, $year);

        return $years;
    }
}
