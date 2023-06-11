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
use CoCoCo\Component\Balancirk\Site\Model\SubscriptionsModel;

\defined('_JEXEC') or die;

/**
 * Balancirk student controller.
 *
 * @since   0.0.1
 */
class SubscriptionsController extends FormController
{
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
	 * Delete subscription
	 *
	 * Delete the subscription for the lesson and the students chosen
	 *
	 * @param	array	$key	List of fields of the form
	 *
	 * @return	void
	 *
	 * @version __BUMP_VERSION__
	 **/
	public function delete(array $key = null)
	{
		// Check if token is correct. Security measure
		$this->checkToken();

		$data = $this->input->get('jform', array(), 'array');

		/** @var SubscriptionModel */
		$model = $this->getModel();

		/** @var CMSApplication */
		$app = Factory::getApplication();

		if ($this->allowDelete($data))
		{
		}

		$this->setRedirect($redirectUrl);
	}
}
