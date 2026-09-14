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
use Joomla\CMS\MVC\Controller\AdminController;
use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel;

/**
 * Controller for the subscriptions list.
 *
 * @since  1.3.21
 */
class SubscriptionsController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.3.21
     */
    protected $text_prefix = 'COM_BALANCIRK_SUBSCRIPTION';

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @since   1.3.21
     */
    public function getModel($name = 'Subscription', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Delete selected subscriptions.
     *
     * @return  void
     *
     * @since   1.3.21
     */
    public function delete()
    {
        $this->checkToken();

        $cid = (array) $this->input->get('cid', [], 'int');
        $cid = array_filter(array_map('intval', $cid));
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        $canDelete = $user->authorise('subscriptions.delete', 'com_balancirk')
            || $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.delete', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk');

        if (!$canDelete) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_balancirk&view=subscriptions', false));

            return;
        }

        if (empty($cid)) {
            $app->enqueueMessage(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_balancirk&view=subscriptions', false));

            return;
        }

        /** @var SubscriptionModel $model */
        $model = $this->getModel();
        $deleted = 0;

        foreach ($cid as $id) {
            if ($model->delete($id)) {
                $deleted++;
            } elseif ($model->getError()) {
                $app->enqueueMessage($model->getError(), 'warning');
            }
        }

        if ($deleted > 0) {
            $app->enqueueMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', $deleted), 'success');
        }

        $this->setRedirect(Route::_('index.php?option=com_balancirk&view=subscriptions', false));
    }
}
