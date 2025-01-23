<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\View\Teacher;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

defined('_JEXEC') or die;

/**
 * View class for the lessons given by a teacher
 * 
 * @since  __BUMP_VERSION__
 */
class HtmlView extends BaseHtmlView
{
	protected $teachers;
	protected $lessons;

	/**
	 * Method to display the view.
	 *
	 * @param  type  $tpl  The template file to include
	 *
	 * @return  BaseController|bool  This object to support chaining.
	 *
	 * @since   __BUMP_VERSION__
	 *
	 * @throws  none
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();

		// Get filter parameters
		$teacherId = $app->input->getInt('teacher_id');
		$startDate = $app->input->get('start_date', '', 'string');
		$endDate = $app->input->get('end_date', '', 'string');

		// Get list of teachers (assuming this is in your model)
		$this->teachers = $this->get('Teachers');

		// Get lessons if a teacher is selected
		if ($teacherId)
		{
			/** @var TeacherModel */
			$model = $this->getModel();
			$this->lessons = $model->getLessonTeached($teacherId, $startDate, $endDate);
		}

		// Display the template
		parent::display($tpl);
	}
}
