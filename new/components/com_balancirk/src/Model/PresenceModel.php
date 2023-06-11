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
use Joomla\Database\QueryInterface;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Presence model for the Joomla Balancirk component.
 *
 * @since  0.0.1
 */
class PresenceModel extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var	string
	 * @since  0.0.1
	 */
	public $typeAlias = 'com_balancirk.presence';

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
	 * Record can only be deleted is there are no entries in the presences table and it is the primary parent
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   0.0.1
	 */
	protected function canDelete($record)
	{
		// TODO: check is the autorization is oké
		if (!empty($record->lesson) || !empty($record->student))
		{
			$app = Factory::getApplication();
			$parentid = $app->getIdentity()->id;

			/** @var PresenceModel */
			$presenceModel = $this->parent::getModel('Presence');

			return $app->getIdentity()->authorise('core.delete', 'com_balancirk.presence.' . (int) $record->id);
		}

		return false;
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
		$form = $this->loadForm($this->typeAlias, 'presence', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Times the student was present in the lesson
	 *
	 * @param   int $student 	Id of the student
	 * @param   int $lesson		Id of the lesson
	 *
	 * @return	integer	Number of times the student was present
	 *
	 * @version __BUMP	_VERSION__
	 **/
	public function numberOfPresences(int $student, int $lesson)
	{
		$db = $this->getDbo();
		/** @var QueryInterface */
		$query = $db->getQuery(true);

		$query->select($db->quote('*'))
			->from($db->quoteName('#__BALANCIRK_PRESENCES'))
			->where(
				$db->quoteName('lesson') . ' = ' . $db->q($lesson),
				$db->quoteName('stduent') . ' = ' . $db->q($student)
			);

		return $db->setQuery($query)->getNumRows();
	}
}
