<?php

/**
 * @package     Joomla.site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2023 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use DateTime;
use DatePeriod;
use DateInterval;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use CoCoCo\Component\Balancirk\Administrator\Model\HolidaysModel;

/**
 * LessonsModel class to display the list off lessons.
 *
 * @since  0.0.1
 */
class LessonModel extends AdminModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var	string
	 * @since  0.0.1
	 */
	public $typeAlias = 'com_balancirk.lesson';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 * @since  0.0.1
	 */
	protected $textPrefix = 'COM_BALANCIRK';

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
		$form = $this->loadForm($this->typeAlias, 'lesson', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the presence row form.
	 *
	 * @param	array   $data	    Data from the form.
	 * @param	boolean $loadData   True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   0.0.1
	 */
	public function getPresenceForm($data = [], $loadData = true)
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
	 * Method to get the studentslist
	 *
	 * List of the students currently subscribed to the lesson
	 *
	 * @return array	an array of students
	 *
	 **/
	public function getStudents()
	{
		// Create a new query object.
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$dbo->quoteName(
				[
					'a.id', 'a.name', 'a.firstname',
					'a.phone', 'a.email', 'a.birthdate',
					'a.allow_photo', 'a.state'
				],
				[
					'id', 'name', 'firstname',
					'phone', 'email', 'birthdate',
					'allow_photo', 'state'
				]
			)
		)
			->from($dbo->quoteName('#__balancirk_students', 'a'))
			->join('INNER', $dbo->quoteName('#__balancirk_subscriptions', 's'), 's.student = a.id')
			->where('s.lesson = ' . $this->getState('lesson.id'));

		$dbo->setQuery($query);

		return $dbo->loadObjectList();
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   1.6
	 */
	protected function canDelete($record)
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();

		return $app->getIdentity()()->authorise('lessons.admin', $this->option);
	}

	/**
	 * Method to test whether a record can have its state changed.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
	 *
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();

		return $app->getIdentity()()->authorise('lessons.admin', $this->option);
	}

	/**
	 * Method to get lesdays of timyint as an array
	 * 
	 * @param int lesdays Number representing days of lesson.
	 *
	 * @return array
	 **/
	public static function getLesdays($lesdays)
	{
		$returnvalue = array();
		$returnvalue["Monday"] = (64 == (64 & $lesdays) ? 1 : 0);
		$returnvalue["Tuesday"] = (32 == (32 & $lesdays) ? 1 : 0);
		$returnvalue["Wednesday"] = (16 == (16 & $lesdays) ? 1 : 0);
		$returnvalue["Thursday"] = (8 == (8 & $lesdays) ? 1 : 0);
		$returnvalue["Friday"] = (4 == (4 & $lesdays) ? 1 : 0);
		$returnvalue["Saturday"] = (2 == (2 & $lesdays) ? 1 : 0);
		$returnvalue["Sunday"] = (1 == (1 & $lesdays) ? 1 : 0);
		return $returnvalue;
	}

	/**
	 * Method to get the dates of the lessons based on startdate, enddate, lesdays and holidays
	 * 
	 * @param	date	$startDate	Starting date of the lessons
	 * @param	date	$endDate	Ending date of the lessons
	 * @param	array	$lesdays	An array of the days of the week the lessons take place
	 * 
	 * @return array dates of the lessons
	 */
	public static function getDates($start, $end, $lesday)
	{
		$period = new DatePeriod(new DateTime($start), new DateInterval('P1D'), new DateTime($end));
		$dates = array();

		foreach ($period as $date)
		{
			// TODO: Check if the date is a holiday

			// Check if date is lesday
			if ($lesday[$date->format('l')] === 1)
			{
				$dates[] = $date;
			}
		}

		return $dates;
	}

	/** 
	 * Method to save the presence of the students
	 * 
	 * @param	int		$id			Id of the lesson
	 * @param	date	$date		Date of the lesson
	 * @param	array	$students	An array of the students present
	 * 
	 * @return void
	 */
	public function savePresence($id, $date, $students)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		// Delete all presences for this lesson
		$query->delete($dbo->quoteName('#__balancirk_presences'))
			->where($dbo->quoteName('lesson') . ' = ' . $id)
			->where($dbo->quoteName('date') . ' = ' . $dbo->quote($date));
		$dbo->setQuery($query);
		$dbo->execute();

		// Insert the new presences
		foreach ($students as $student)
		{
			$query->clear();
			$query->insert($dbo->quoteName('#__balancirk_presences'))
				->columns($dbo->quoteName(['lesson', 'student', 'date']))
				->values($id . ', ' . $student . ', ' . $dbo->quote($date));
			$dbo->setQuery($query);
			$dbo->execute();
		}
	}
}
