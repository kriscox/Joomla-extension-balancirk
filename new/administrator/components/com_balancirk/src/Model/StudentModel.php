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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Jooma\CMS\CMSApplicationInterface;

/**
 * Item model for student.
 *
 * @since  0.0.1
 */
class StudentModel extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $typeAlias = 'com_balancirk.student';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	protected $text_prefix = 'COM_BALANCIRK';

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return  Table  A Table object
	 *
	 * @since   0.0.1
	 * @throws  \Exception
	 */
	public function getTable($name = '', $prefix = '', $options = array())
	{
		$name = 'students';
		$prefix = 'Table';

		if ($table = $this->_createTable($name, $prefix, $options))
		{
			return $table;
		}

		throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name), 0);
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
		$form = $this->loadForm($this->typeAlias, 'student', ['control' => 'jform', 'load_data' => $loadData]);

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
	 * @since  0.0.1
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		// @var CMSApplicationInterface
		$app = Factory::getApplication();
		$data = $app->getUserState('com_balancirk.edit.student.data', array());



		if (empty($data))
		{
			$data = $this->getItem();

			// Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
		}

		$this->preprocessData($this->typeAlias, $data);

		return $data;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    $pks    A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  void  True on success.
	 *
	 * @since   0.0.1
	 */
	public function publish(&$pks, $value = 1)
	{
		// This is a very simple method to change the state of each item selected
		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query->update('`#__balancirk_students`');
		$query->set('state = ' . $value);
		$query->where('id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);
		$db->execute();
	}
}
