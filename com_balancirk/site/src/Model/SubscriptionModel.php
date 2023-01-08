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
use CoCoCo\Component\Balancirk\Site\Model\StudentModel;
use CoCoCo\Component\Balancirk\Site\Model\PresenceModel;

/**
 * Subscription model for the Joomla Balancirk component.
 *
 * @since  0.0.1
 */
class SubscriptionModel extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var	string
	 * @since  0.0.1
	 */
	public $typeAlias = 'com_balancirk.subscription';

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
		if (!empty($record->lesson) || !empty($record->student))
		{
			$app = Factory::getApplication();
			$parentid = $app->getIdentity()->id;

			/** @var StudentModel */
			$studentModel = $this->getMVCFactory()->createModel('Students', 'Site');
			/** @var PresenceModel */
			$presenceModel = $this->getMVCFactory()->createModel('Presence', 'Site');

			return ($studentModel->isPrimairyParent($parentid, $record->student) &&
				($presenceModel->numberOfPresences($record->student, $record->lesson) <= 2) &&
				$app->getIdentity()->authorise('core.delete', 'com_balancirk.subscription.' . (int) $record->id)
			);
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
		$name = 'subscriptions';
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
		$form = $this->loadForm($this->typeAlias, 'subscription', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the students from the user.
	 *
	 * @return  array  An array with all students
	 *
	 * @since   __BUMP_VERSION__
	 */
	public function getStudents()
	{
		/** @var studentsModel */
		$model = $this->getMVCFactory()->createModel('Students', 'Site');

		return $model->getItems();
	}

	/**
	 * Method to get the lessons which are open
	 *
	 * @return  array	An array with all lessons which are open to subscribe
	 *
	 * @since   __BUMP_VERSION__
	 */
	public function getLessons()
	{
		/** @var lessonsModel */
		$model = $this->getMVCFactory()->createModel('Lessons', 'Site');

		return $model->getItems();
	}
}
