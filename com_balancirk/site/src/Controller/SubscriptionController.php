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
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use CoCoCo\Component\Balancirk\Site\Model\StudentModel;
use CoCoCo\Component\Balancirk\Site\Model\PresenceModel;
use CoCoCo\Component\Balancirk\Site\Model\SubscriptionModel;

\defined('_JEXEC') or die;

/**
 * Balancirk student controller.
 *
 * @since   0.0.1
 */
class SubscriptionController extends FormController
{
	/**
	 * Cancel and return to the students list page.
	 *
	 * Implement the cancel button to return to the subscriptions list page on pressing the button with
	 * task subscriptions.cancel
	 *
	 * @param   array	   $key	List of fields of the for
	 *
	 * @return	void
	 *
	 * @since   __BUMP_VERSION__
	 **/
	public function cancel($key = null)
	{
		parent::cancel($key);

		// Set up the redirect back to the previous page (put in the header in HtmlView.php)
		$this->redirect(
			'/administrator/index.php?option=' . $this->option . '&view=subscriptions'
		);
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = array())
	{
		$user 	= Factory::getApplication()->getIdentity();

		/** @var StudentModel */
		$model	= $this->getModel('Student');

		// Check if the user is the primary parent of the student
		return $model->isPrimairyParent($user->id, $data['student']);
	}

	/**
	 * Method to check if you can delete a new record.
	 *
	 * Delete the record if no more than 2 presences exists for the subscription
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowDelete($data = array())
	{
		$user	= Factory::getApplication()->getIdentity();

		/** @var StudentModel */
		$studentModel = $this->getModel('Student');
		/** @var PresenceModel */
		$presenceModel = $this->getModel('Presence');

		return ($studentModel->isPrimairyParent($user->id, $data['student']) &&
			$presenceModel->numberOfPresences($data['student'], $data['lesson']) <= 2);
	}

	/**
	 * Add subscription
	 *
	 * Add the subscription for the lesson and the students chosen
	 *
	 * @param	array	$key	List of fields of the form
	 *
	 * @return	void
	 *
	 * @version __BUMP_VERSION__
	 **/
	public function add(array $key = null)
	{
		// Check if token is correct. Security measure
		$this->checkToken();

		$data = $this->input->get('jform', array(), 'array');

		/** @var SubscriptionModel */
		$model = $this->getModel();

		/** @var CMSApplication */
		$app = Factory::getApplication();
		$app->setUserState('com_balancirk.subscription.data', $data);

		if ($this->allowAdd($data))
		{
			if ($model->add($data))
			{
				$app->setUserState('com_balancirk.subscription.data', null);
				$redirectUrl = Route::_('index.php?option=' . $this->option . '&view=subscriptions');
			}
			else
			{
				$redirectUrl = Route::_('index.php?option=' . $this->option . '&view=subscription');
			}
		}

		$this->setRedirect($redirectUrl);
	}
}
