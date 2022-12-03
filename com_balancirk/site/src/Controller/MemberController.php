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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\MVC\Controller\FormController;
use CoCoCo\Component\Balancirk\Site\Model\MemberModel;

\defined('_JEXEC') or die;

/**
 * Balancirk member controller.
 *
 * @since   0.0.1
 */
class MemberController extends FormController
{
	/**
	 * Cancel and return to homepage
	 *
	 * Implement the cancel button to return to the homepage on pressing the button with
	 * task member.cancel
	 *
	 * @param   array	   $key	List of fields of the for
	 *
	 * @since   __BUMP_VERSION__
	 **/
	public function cancel($key = null)
	{
		parent::cancel($key);

		// Set up the redirect back to the previous page (put in the header in HtmlView.php)
		$this->setRedirect("/");
	}

	/**
	 * Register user
	 *
	 * Register the user based on the input values in $key
	 *
	 * @param   array	   $key	List of fields of the form
	 *
	 * @return	void
	 *
	 * @since   __BUMP_VERSION__
	 **/
	public function register($key = null)
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
		$redirectUrl = Route::_('index.php?option=com_balancirk&view=member&layout=register', false);

		// Validate data and fill form data cache
		$validData = $model->validate($form, $data);
		$app->setUserState('com_balancirk.member.data', $data);

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
			$app->setUserState('com_balancirk.member.data', null);

			// Set return to homepage
			$redirectUrl = Route::_('/', false);
		}

		// Redirect back to the form in all cases
		$this->setRedirect($redirectUrl);
	}
}
