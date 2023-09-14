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

use DateInterval;
use Exception;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * LessonsModel class to display the list off lessons.
 *
 * @since  0.0.1
 */
class LessonsModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JControllerLegacy
	 * @see     \Joomla\CMS\MVC\Controller\BaseController
	 *
	 * @since   __BUMP_VERSION__
	 */
	public function __construct($config = [])
	{

		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'name', 'a.name',
				'type', 'a.type',
				'year', 'a.year',
				'numberOfStudents', 'a.numberOfStudents',
				'state', 'a.state'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string $ordering  The name of the ordering field.
	 * @param   string $direction The direction of ordering (asc|desc).
	 * @return  void
	 * @throws  Exception If the state is not an object.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = \JFactory::getApplication();

		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');

		$this->setState('filter.search', $search);

		// List state information.
		parent::populateState('a.name', 'asc');

		// Set limit
		$this->setState('list.limit', $_REQUEST['limit'] ?? 0);

		// Set start (eg. what record to begin pagination at)
		$this->setState('list.start', $_REQUEST['limitstart'] ?? 0);
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

		// Get the current date
		$today = date("Y-m-d");

		// Select the required fields from the table.
		$query->select(
			$db->quoteName(
				[
					'a.id', 'a.name', 'a.type',
					'a.year', 'a.state', 'a.numberOfStudents'
				],
				[
					'id', 'name', 'type',
					'year', 'state', 'numberOfStudents'
				]
			)
		)
			->from($db->quoteName('#__balancirk_lessons_complete', 'a'));

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (empty($search))
		{
			$query->where($db->quote(date('Y', strtotime($today . '- 5 months'))) . ' = `year`');
		}
		else
		{
			$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
			$query->where('(a.name LIKE ' . $search . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.name');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

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

	/**
	 * Method to get list of lessons open for subscription
	 *
	 * Lessons open for subscription.
	 *
	 * @return array
	 **/
	public function getOpenLessons()
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
			->where($db->quote($today) . ' between `start_registration` and `end_registration`')
			->order('name');

		$rows = $db->setQuery($query)->loadObjectlist();

		foreach ($rows as $row)
		{
			$lessons[$row->id] = $row->name;
		}

		return $lessons;
	}

	/**
	 * Method to get list of current lessons 
	 *
	 * Current active lessons.
	 *
	 * @return array
	 **/
	public function getCurrentLessons()
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
			->where($db->quote($today) . ' between `start` and `end`')
			->order('name');

		$rows = $db->setQuery($query)->loadObjectlist();

		foreach ($rows as $row)
		{
			$lessons[$row->id] = $row->name;
		}

		return $lessons;
	}
}
