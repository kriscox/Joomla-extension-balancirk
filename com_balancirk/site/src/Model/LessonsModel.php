<?php

/**
 * @package     Joomla.site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * LessonsModel class to display the list off lessons.
 *
 * @since  0.0.1
 */
class LessonsModel extends ListModel
{
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

		// Get the current date
		$today = date("Y-m-d");

		// Select the required fields from the table.
		$query->select(
			$db->quoteName(
				[
					'id', 'name', 'type',
					'fee', 'year', 'state'
				]
			)
		)
			->from($db->quoteName('#__balancirk_lessons', 'a'))
			->where($today . ' between `start_date` and `end_date`');

		$query->order('name');

		return $query;
	}

	/**
	 * Method to get a list of walks.
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
