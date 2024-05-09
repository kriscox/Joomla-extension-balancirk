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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;

\defined('_JEXEC') or die;

/**
 * Balancirk lesson controller.
 *
 * @since   0.0.1
 */
class LessonController extends FormController
{
    /**
     * Cancel and return to the students list page.
     *
     * Implement the cancel button to return to the students list page on pressing the button with
     * task student.cancel
     *
     * @param   array	   $key	List of fields of the for
     *
     * @since   __BUMP_VERSION__
     **/
    public function cancel($key = null)
    {
        parent::cancel($key);

        // Set up the redirect back to the previous page (put in the header in HtmlView.php)
        $this->redirect(
            '/index.php?option=' . $this->option . '&view=lessons'
        );
    }

    /**
     * Save the form data for presences.
     *
     * @param   string  $key	The name of the key for the primary key.
     * @param   string  $url	The URL to redirect to on success.
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   __BUMP_VERSION__
     */
    public function presence($key = null, $url = '')
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get the curren application
        /** @var CMSApplication */
        $app = Factory::getApplication();

        // Get the data from the form POST
        $data = $this->input->post->get('jform', [], 'array');

        // Get the model and the form used
        /** @var LessonModel */
        $model = $this->getModel('Lesson');
        $form = $model->getForm($data, false);

        // Convert date from nl-BE to 'Y-m-d'
        $data['date'] = date('Y-m-d', strtotime(str_replace('/', '-', $data['date'])));

        // Set the default rediection url
        $redirectUrl = Route::_('index.php?option=' . $this->option . '&view=lesson&id=' . $data['id'], false);

        // Fill form data cache
        $app->setUserState('com_balancirk.presence.data', $data);

        $model->savePresence($data['id'], $data['date'], $data['students']);

        // Set success message
        $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_PRESENCE_SAVED'), 'success');

        // Redirect to the lesson page
        $this->setRedirect($redirectUrl);
    }
}
