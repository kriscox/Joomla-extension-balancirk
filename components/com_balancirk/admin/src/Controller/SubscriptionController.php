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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Controller\FormController;
use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel;

/**
 * Controller for a single subscription.
 *
 * @since  1.3.21
 */
class SubscriptionController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.3.21
     */
    protected $text_prefix = 'COM_BALANCIRK_SUBSCRIPTION';

    /**
     * Method to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   1.3.21
     */
    protected function allowAdd($data = [])
    {
        $user = Factory::getApplication()->getIdentity();

        return $user->authorise('subscriptions.create', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk');
    }

    /**
     * Method to check if you can delete a subscription.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   1.3.21
     */
    protected function allowDelete($data = [])
    {
        $user = Factory::getApplication()->getIdentity();

        return $user->authorise('subscriptions.delete', 'com_balancirk')
            || $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.delete', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk');
    }

    /**
     * Enrol a student in a lesson.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key.
     *
     * @return  boolean
     *
     * @since   1.3.21
     */
    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $data = $this->input->post->get('jform', [], 'array');
        $return = $this->input->get('return', '', 'base64');

        /** @var SubscriptionModel $model */
        $model = $this->getModel('Subscription');
        $app->setUserState('com_balancirk.edit.subscription.data', $data);

        $redirectUrl = $this->resolveRedirect($return, 'index.php?option=com_balancirk&view=subscription&layout=edit');

        if (!$this->allowAdd($data)) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'warning');
            $this->setRedirect($redirectUrl);

            return false;
        }

        if ($model->add($data)) {
            $app->setUserState('com_balancirk.edit.subscription.data', null);
            $app->enqueueMessage(Text::_('COM_BALANCIRK_SUBSCRIPTION_SAVED'), 'success');
            $this->setRedirect($this->resolveRedirect($return, 'index.php?option=com_balancirk&view=subscriptions'));

            return true;
        }

        if ($model->getError()) {
            $app->enqueueMessage($model->getError(), 'warning');
        }

        $this->setRedirect($redirectUrl);

        return false;
    }

    /**
     * Delete one subscription by id.
     *
     * @return  void
     *
     * @since   1.3.21
     */
    public function delete()
    {
        $this->checkToken('request');

        $app = Factory::getApplication();
        $id = $this->input->getInt('id');
        $return = $this->input->get('return', '', 'base64');
        $redirectUrl = $this->resolveRedirect($return, 'index.php?option=com_balancirk&view=subscriptions');

        if ($id <= 0) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETE_NOT_FOUND'), 'warning');
            $this->setRedirect($redirectUrl);

            return;
        }

        /** @var SubscriptionModel $model */
        $model = $this->getModel('Subscription');
        $item = $model->getItem($id);

        if (!$item || (int) ($item->id ?? 0) <= 0) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETE_NOT_FOUND'), 'warning');
            $this->setRedirect($redirectUrl);

            return;
        }

        $data = [
            'id' => (int) $item->id,
            'student' => (int) $item->student,
            'lesson' => (int) $item->lesson,
        ];

        if (!$this->allowDelete($data)) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'warning');
            $this->setRedirect($redirectUrl);

            return;
        }

        if (!$model->delete($id)) {
            $app->enqueueMessage(
                $model->getError() ?: Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETE_FAILED'),
                'warning'
            );
            $this->setRedirect($redirectUrl);

            return;
        }

        $app->enqueueMessage(Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETED'), 'success');
        $this->setRedirect($redirectUrl);
    }


    /**
     * Cancel enrolment form and redirect.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean
     *
     * @since   1.3.21
     */
    public function cancel($key = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $app->setUserState('com_balancirk.edit.subscription.data', null);

        $return = $this->input->get('return', '', 'base64');
        $this->setRedirect($this->resolveRedirect($return, 'index.php?option=com_balancirk&view=subscriptions'));

        return true;
    }

    /**
     * Resolve a safe redirect URL.
     *
     * @param   string  $return   Base64-encoded return URL.
     * @param   string  $default  Default internal URL.
     *
     * @return  string
     *
     * @since   1.3.21
     */
    private function resolveRedirect(string $return, string $default): string
    {
        if ($return !== '') {
            $decoded = base64_decode($return);

            if (is_string($decoded) && $decoded !== '' && Uri::isInternal($decoded)) {
                return Route::_($decoded, false);
            }
        }

        return Route::_($default, false);
    }
}
