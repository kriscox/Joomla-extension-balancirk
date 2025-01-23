<?php

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\MVC\Controller\FormController;

defined('_JEXEC') or die;

/**
 * Balancirk teacher controller.
 *
 * @since   __BUMP_VERSION__
 */
class BalancirkControllerTeachers extends FormController
{
	/**
	 * .
	 *
	 * @param  
	 *
	 * @return  BaseController|bool  This object to support chaining.
	 *
	 * @since   0.0.1
	 *
	 * @throws  
	 */
	public function filterLessons()
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();

		// Get teacher ID and date range from the request
		$teacherId = $this->input->getInt('teacher_id');
		$startDate = $this->input->get('start_date', '', 'string');
		$endDate = $this->input->get('end_date', '', 'string');

		// Redirect back to the view with filters applied
		$app->redirect(
			JRoute::_('index.php?option=com_balancirk&view=teachers&teacher_id=' . $teacherId . '&start_date=' . $startDate . '&end_date=' . $endDate, false)
		);
	}
}
