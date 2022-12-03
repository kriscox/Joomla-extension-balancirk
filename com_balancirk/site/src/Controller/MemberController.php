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

		// Get data from the form
		$formData = $this->input->get('jform', [], 'array');

		// Create a new memberModel to create the user
		$model = new MemberModel;

		$app = Factory::getApplication();

		// Set happy redirect, we will change it in case of errors
		$this->setRedirect("/");

		// Register the user
		try
		{
			$model->register($formData);
		}
		catch (\UnexpectedValueException $e)
		{
			$app->enqueueMessage(Text::_("COM_BALANCIRK_USER_ERROR") . $e->getMessage(), 'error');
			$this->setRedirect($uri = Uri::getInstance());
		}
		catch (\RuntimeException $e)
		{
			$app->enqueueMessage(Text::_("COM_BALANCIRK_USER_ERROR") . $e->getMessage(), 'error');
			$this->setRedirect($uri = Uri::getInstance());
		}
	}
}
