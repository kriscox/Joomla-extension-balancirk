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
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Controller for a single member.
 *
 * @since  0.0.1
 */
class MemberController extends FormController
{
	/*
	* Not necessairy as he caluclates it itself
	*
	* protected $view_item = 'member';
	* protected $view_list = 'members';
	*
	*/

	/**
	 *  Register a member in Joomla
	 *
	 * @return	void
	 *
	 */
	public function register()
	{
		// Check if token is correct. Security measure
		$this->checkToken();

		// Get the curren application
		$app = Factory::getApplication();

		// Get data from the form
		$data = $this->input->get('jform', array(), 'array');

		// Get the model and the form used
		$model = $this->getModel('member');
		$form = $model->getForm($data, false);

		// Set the default redirection url
		$redirectUrl = Route::_('index.php?option=com_balancirk&view=member&layout=edit', false);

		// Validate data and fill form data cache
		$validData = $model->validate($form, $data);
		$app->setUserState('com_balancirk.edit.member.data', $data);

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
		}


		// Register the user
		if ($model->register($data))
		{
			// Rmove the form data in the session, using a unique identifier
			$app->setUserState('com_balancirk.edit.member.data', null);

			// Set return to homepage
			$redirectUrl = Route::_('/administrator/index.php?option=com_balancirk&view=members', false);
		}

		// Redirect back to the form in all cases
		$this->setRedirect($redirectUrl);
	}
}
