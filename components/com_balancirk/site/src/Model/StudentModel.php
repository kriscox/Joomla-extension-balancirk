<?php

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Application\CMSApplicationInterface;

/**
 * Student model for the Joomla Balancirk component.
 *
 * @since  0.0.1
 */
class StudentModel extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var	string
	 * @since  0.0.1
	 */
	public $typeAlias = 'com_balancirk.student';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 * @since  0.0.1
	 */
	protected $textPrefix = 'COM_BALANCIRK';

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   0.0.1
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			$app = Factory::getApplication();

			return $app->getIdentity()->authorise('core.delete', 'com_balancirk.students.' . (int) $record->id);
		}

		return false;
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   0.0.1
	 */
	protected function canEditState($record)
	{
		$student = Factory::getApplication()->getIdentity();

		// Check for existing article.
		if (!empty($record->id))
		{
			return $student->authorise('core.edit.state', 'com_balancirk.students.' . (int) $record->id);
		}

		// Default to component settings if neither article nor category known.
		return parent::canEditState($record);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name	  The table name. Optional.
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
	 * @param	array   $data	    Data from the form.
	 * @param	boolean $loadData   True if the form is to load its own data (default case), false if not.
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
	 * @since   0.0.1
	 */
	protected function loadFormData()
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();

		$data = $app->getUserState('com_balancirk.student.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData($this->typeAlias, $data);

		return $data;
	}

	/**
	 * Method to save the student
	 *
	 * Save the student and fill the user as primairy parent
	 *
	 * @param 	array $data The form data.
	 *
	 * @return 	boolean		True on success, False on error.
	 *
	 * @since	__BUMP_VERSION__
	 **/
	public function save($data)
	{
		// Set default value of student to unsubscribed
		$data['state'] = 1;

		// If save is successfull and this is a new student than fill the user as primairy parent
		if (parent::save($data))
		{
			if ($this->state->get("student.new"))
			{
				$columns = array('child', 'parent', 'primary');

				// Get the new student.id
				$values = array($this->state->get('student.id'));

				// Get the current user id as parent
				array_push($values, \Joomla\CMS\Factory::getApplication()->getIdentity()->id);

				// Set the parent as primairy
				array_push($values, 1);

				$db = $this->getDatabase();
				$query = $db->getQuery(true);
				$query->insert($db->quoteName('#__balancirk_parents'))
					->columns($db->quoteName($columns))
					->values(implode(',', $values));
				$db->setQuery($query)->execute();

				return true;
			}
		}

		return true;
	}

	/**
	 * Check if parent is primairy
	 *
	 * Check if the given parent id is the primairy parent of the student.
	 *
	 * @param	int	$parent		Parent id
	 * @param	int	$student	Student id
	 *
	 * @return	boolean	true if it is the primary parent id in other cases false
	 *
	 * @since __BUMP_VERSION__
	 */
	public function isPrimairyParent(int $parent = null, int $student = null)
	{
		// Check if the user is the primary parent of the student
		$db 	= $this->getDatabase();
		$query 	= $db->getQuery(true);
		$query->select($db->quote('*'))
			->from($db->quoteName('#__balancirk_parents'))
			->where($db->quoteName('child') . ' = ' . $db->quote($student))
			->where($db->quoteName('parent') . ' = ' . $db->quote($parent))
			->where($db->quoteName('primary') . ' = 1');

		if ($db->setQuery($query)->execute())
		{
			return $db->getNumRows() >= 1;
		}
		else
		{
			return false;
		}
	}
}
