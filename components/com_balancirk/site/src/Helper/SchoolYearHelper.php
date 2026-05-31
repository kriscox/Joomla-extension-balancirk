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
}
