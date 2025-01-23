<?php

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

namespace ComBalancirk\Component\Balancirk\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Teacher model for the Joomla Balancirk component.
 *
 * @since  __BUMP_VERSION__
 */
class TeacherModel extends BaseDatabaseModel
{
	/**
	 * The type alias for this content type.
	 *
	 * @var	string
	 * @since  __BUMP_VERSION__
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
	 * Get the teached lessons of the teacher.
	 *
	 * @param	int	$teacherId	Teacher id
	 * @param	string	$startDate	Starting date
	 * @param	string	$endDate	Ending date
	 *
	 * @return	array List of teached lessons
	 *
	 * @since __BUMP_VERSION__
	 */
	public function getLessonsTeached($teacherId, $startDate, $endDate)
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->quoteName(
				[
					't.id',
					't.teacher',
					't.date',
					'l.name'
				],
				[
					'id',
					'teacher',
					'date',
					'lesson'
				]
			))
			->from($this->db->quoteName('#__balancirk_teached', 't'))
			->join(
				'INNER',
				$this->db->quoteName('#__balancirk_lessons', 'l'),
				$this->db->quoteName('t.lesson') . ' = ' . $this->db->quoteName('l.id')
			)
			->where($this->db->quoteName('teacher') . ' = ' . (int) $teacherId)
			->where($this->db->quoteName('date') . ' BETWEEN ' . $this->db->quote($startDate) . ' AND ' . $this->db->quote($endDate));

		$this->db->setQuery($query);
		return $this->db->loadObjectList();
	}

	/**
	 * Get the list of teachers.
	 *
	 * @return	array List of teachers
	 *
	 * @since __BUMP_VERSION__
	 */
	public function getTeachers()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->quoteName(
				[
					't.member',
					'm.name',
					'm.firstname'
				],
				[
					'id',
					'name',
					'firstname'
				]
			))
			->from($db->quoteName('#__balancirk_teachers', 't'))
			->join(
				'INNER',
				$db->quoteName('#__balancirk_members', 'm'),
				$db->quoteName('t.member') . ' = ' . $db->quoteName('m.id')
			);

		return $db->setQuery($query)->loadObjectList();
	}
}
