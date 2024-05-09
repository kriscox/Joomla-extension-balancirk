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
     * Save member profile in joomla
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     **/
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get the curren application
        $app = Factory::getApplication();

        // Get data from the form
        $data = $this->input->post->get('jform', array(), 'array');

        // Get the model and the form used
        $model = $this->getModel();
        $form = $model->getForm($data, false);

        // Access check.
        if (!$this->allowSave(array($data, $key))) {
            $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_list
                        . $this->getRedirectToListAppend(),
                    false
                )
            );

            return false;
        }

        // Set the default redirection url
        $this->setRedirect(
            Route::_(
                'index.php?option=' . $this->option . '&view=member&layout=edit',
                false
            )
        );

        // Validate data and fill form data cache
        $validData = $model->validate($form, $data);
        $app->setUserState('com_balancirk.edit.member.data', $data);

        if ($validData === false) {
            $errors = $model->getErrors();

            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    $app->enqueueMessage($error->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($error, 'warning');
                }
            }

            return false;
        }

        // Save the changes to the profile
        if ($model->edit($data)) {
            // Rmove the form data in the session, using a unique identifier
            $app->setUserState('com_balancirk.edit.member.data', null);

            // Set return to homepage
            $redirectUrl = Route::_(
                '/administrator/index.php?option=' . $this->option . '&view=members',
                false
            );
        }

        // Redirect back to the form in all cases
        $this->setRedirect($redirectUrl);

        return true;
    }

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
        $redirectUrl = Route::_('index.php?option=' . $this->option . '&view=member&layout=edit', false);

        // Validate data and fill form data cache
        $validData = $model->validate($form, $data);
        $app->setUserState('com_balancirk.edit.member.data', $data);

        if ($validData === false) {
            $errors = $model->getErrors();

            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    $app->enqueueMessage($error->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($error, 'warning');
                }
            }
        }

        // Register the user
        if ($model->register($data)) {
            // Rmove the form data in the session, using a unique identifier
            $app->setUserState('com_balancirk.edit.member.data', null);

            // Set return to homepage
            $redirectUrl = Route::_('/administrator/index.php?option=' . $this->option . '&view=members', false);
        }

        // Redirect back to the form in all cases
        $this->setRedirect($redirectUrl);
    }
}
