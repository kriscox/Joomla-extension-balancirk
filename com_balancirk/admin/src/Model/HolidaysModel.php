<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterHoliday;

/**
 * HolidaysModel class to display the list off holidays.
 *
 * @since  0.0.1
 */
class HolidaysModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JControllerLegacy
	 *
	 * @since   __BUMP_VERSION__
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'year', 'a.year',
				'startDate', 'a.startDate',
				'endDate', 'a.endDate',
				'Summary', 'a.summary'
			);
		}

		parent::__construct($config);
	}


	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  \JDatabaseQuery
	 *
	 * @since   __BUMP_VERSION__
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$db->quoteName(
				[
					'a.year', 'a.startDate',
					'a.endDate', 'a.summary'
				]
			)
		);
		$query->from($db->quoteName('#__balancirk_holidays', 'a'));

		return $query;
	}

	/**
	 * Method to get a list of holidays.
	 * Overridden to add a check for access levels.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __BUMP_VERSION__
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}
}
