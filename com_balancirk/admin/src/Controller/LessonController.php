<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Controller for a single student.
 *
 * @since  0.0.1
 */
class LessonController extends FormController
{
	/**
	 * Save lesson information 
	 * 
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).	
	 * @return  boolean  True if successful, false otherwise.
	 * 
	 * @since   0.0.1
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		$this->checkToken();

		// Get the curren application
		/** @var CMSFactory $app */
		$app = Factory::getApplication();

		// Get data from the form
		$data = $this->input->post->get('jform', array(), 'array');

		// Get the model and the form used
		/** @var LessonModel $model */
		$model = $this->getModel();
		$form = $model->getForm($data, false);

		// Access check.
		if (!$this->allowSave(array($data, $key)))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
					false
				)
			);

			return false;
		}

		// Set the default redirection url
		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=lessons',
				false
			)
		);

		// Validate data and fill form data cache
		$validData = $model->validate($form, $data);
		$app->setUserState($this->context . '.data', $validData);

		// Add lesdays to the data
		$lesdaysField = $data["lesdays_field"];
		$lesday = 0;
		foreach ($lesdaysField as $day)
		{
			$lesday += $day;
		}
		$validData['lesdays'] = $lesday;

		if ($validData === false)
		{
			$errors = $model->getErrors();

			foreach ($errors as $error)
			{
				if ($error instanceof \Exception)
				{
					$app->enqueueMessage($error->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($error, 'warning');
				}
			}

			// Stay on page in case of error
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend() . '&id=' . $this->input->get('id'),
					false
				)
			);

			return false;
		}

		// Save the changes to the profile
		$model->save($validData);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_BALANCIRK_LESSON_SAVE_SUCCESS'));

		return true;
	}
}
