<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Table;

\defined('_JEXEC') or die;

use CoCoCo\Component\Balancirk\Site\Helper\HolidayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Holiday table class.
 *
 * @since  0.0.1
 */
class HolidaysTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   __BUMP_VERSION__
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->holidayAlias = 'com_balancirk.holidays';
        parent::__construct('#__balancirk_holidays', 'id', $db);
    }

    /**
     * Validate holiday dates before storing.
     *
     * @return  boolean  True on success.
     *
     * @since   1.3.24
     */
    public function check()
    {
        if ((int) $this->year <= 0) {
            $this->setError(Text::_('COM_BALANCIRK_HOLIDAY_YEAR_REQUIRED'));

            return false;
        }

        $dates = HolidayHelper::resolveStoredDates($this->startDate, $this->endDate);
        $start = $dates['start'];
        $end = $dates['end'];

        if ($start !== '') {
            $this->startDate = $start;
        }

        if ($end !== '') {
            $this->endDate = $end;
        }

        if ($start === '' || $end === '') {
            $this->setError(Text::_('COM_BALANCIRK_HOLIDAY_DATES_REQUIRED'));

            return false;
        }

        if ($start > $end) {
            $this->setError(Text::_('COM_BALANCIRK_HOLIDAY_DATES_INVALID'));

            return false;
        }

        return parent::check();
    }
}
