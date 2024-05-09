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

use Joomla\CMS\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Contoller for a list of members.
 *
 * @since	0.0.1
 */
class MembersController extends FormController
{
    /**
     * Delete one or more members
     *
     * Deletes the selected users in the list of members
     * and calls delete on the member
     *
     * @return void
     **/
    public function delete()
    {
        // Check if token is correct. Security measure
        $this->checkToken();

        // Get items to remove from the request.
        $cid = (array) $this->input->get('cid', array(), 'int');

        // Remove zero values resulting from input filter
        $cid = array_filter($cid);

        if (empty($cid)) {
            $this->app->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), array('category' => 'jerror'));
        } else {
            // Get the model.
            $model = $this->getModel('members');

            // Remove the items.
            if ($model->delete($cid)) {
                $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', \count($cid)));
            } else {
                $this->setMessage($model->getError(), 'error');
            }

            // Invoke the postDelete method to allow for the child class to access the model.
            $this->postDeleteHook($model, $cid);
        }

        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));
    }
}
