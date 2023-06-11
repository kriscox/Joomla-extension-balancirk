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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Form\FormFactoryAwareInterface;

/**
 * HolidaysModel class to display the list off holidays.
 *
 * @since  0.0.1
 */
class HolidaysModel extends ListModel implements FormFactoryAwareInterface
{
	/**
	 * The holiday alias for this content holiday.
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $holidaysAlias = 'com_balancirk.holidays';

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

	/**
	 * Method to get the row form.
	 *
	 * @param   array   $data       Data from the form.
	 * @param   boolean $loadData   True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   0.0.1
	 */
	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->holidaysAlias, 'holidays', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   0.0.1
	 */
	protected function loadFormData()
	{
		$app = Factory::getApplication();

		$data = $this->getItems();

		$this->preprocessData($this->holidaysAlias, $data);

		return $data;
	}
}
