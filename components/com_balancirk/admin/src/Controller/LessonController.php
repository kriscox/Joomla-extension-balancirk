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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use CoCoCo\Component\Balancirk\Site\Helper\LesdaysHelper;

/**
 * Controller for a single student.
 *
 * @since  0.0.1
 */
class LessonController extends FormController
{
    /**
     * Save lesson information
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   0.0.1
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get the curren application
        /** @var CMSFactory $app */
        $app = Factory::getApplication();

        // Get data from the form
        $data = $this->input->post->get('jform', array(), 'array');

        // Get the model and the form used
        /** @var LessonModel $model */
        $model = $this->getModel();
        $form = $model->getForm($data, false);

        // Access check.
        if (!$this->allowSave(array($data, $key))) {
            $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
                    false
                )
            );

            return false;
        }

        // Set the default redirection url
        $this->setRedirect(
            Route::_(
                'index.php?option=' . $this->option . '&view=lessons',
                false
            )
        );

        // Validate data and fill form data cache
        $validData = $model->validate($form, $data);
        $app->setUserState($this->context . '.data', $validData);

        if ($validData === false) {
            $errors = $model->getErrors();

            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    $app->enqueueMessage($error->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($error, 'warning');
                }
            }

            // Stay on page in case of error
            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend() . '&id=' . $this->input->get('id'),
                    false
                )
            );

            return false;
        }

        $validData['lesdays'] = LesdaysHelper::fromFormValues($data['lesdays_field'] ?? []);
        unset($validData['lesdays_field']);

        // Teachers are optional for API/SPA partial updates.
        // The admin edit form always sends teachers_sync=1 so checkbox changes are applied
        // (including "uncheck all", which would otherwise omit the teachers key).
        if (\array_key_exists('teachers_sync', $data) || \array_key_exists('teachers', $data)) {
            $validData['teachers'] = (isset($data['teachers']) && \is_array($data['teachers']))
                ? $data['teachers']
                : [];
        }

        // Save the changes to the profile
        if (!$model->save($validData)) {
            $errors = $model->getErrors();

            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    $app->enqueueMessage($error->getMessage(), 'error');
                } else {
                    $app->enqueueMessage($error, 'error');
                }
            }

            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend() . '&id=' . $this->input->get('id'),
                    false
                )
            );

            return false;
        }

        // Redirect to the list screen.
        $this->setMessage(Text::_('COM_BALANCIRK_LESSON_SAVE_SUCCESS'));

        return true;
    }

    /**
     * Settle waitlist (promote / dismiss) for year-start.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function settleWaitlist(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $id = $this->input->getInt('id');
        $promoteIds = (array) $this->input->get('promote_ids', [], 'array');
        $dismissIds = (array) $this->input->get('dismiss_ids', [], 'array');
        $confirmFifo = (int) $this->input->getInt('confirm_fifo_override', 0) === 1;
        $closeRegistration = (int) $this->input->getInt('close_registration', 1) === 1;

        /** @var \CoCoCo\Component\Balancirk\Administrator\Model\LessonModel $model */
        $model = $this->getModel();
        $result = $model->settleWaitlist($id, $promoteIds, $dismissIds, $confirmFifo, $closeRegistration);

        if ($result === false) {
            $app->enqueueMessage($model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), 'error');
        } else {
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_BALANCIRK_WAITLIST_SETTLE_SUCCESS',
                    (int) $result['promoted'],
                    (int) $result['dismissed']
                ),
                'success'
            );

            if (!empty($result['closed'])) {
                $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_REGISTRATION_CLOSED_NOTICE'), 'message');
            }
        }

        $this->setRedirect(
            Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . $id, false)
        );
    }

    /**
     * Close registrations for a lesson.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function closeRegistration(): void
    {
        $this->checkToken('get');
        $id = $this->input->getInt('id');
        /** @var \CoCoCo\Component\Balancirk\Administrator\Model\LessonModel $model */
        $model = $this->getModel();
        $model->closeRegistration($id);
        Factory::getApplication()->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_REGISTRATION_CLOSED_NOTICE'), 'success');
        $this->setRedirect(Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . $id, false));
    }

    /**
     * Reopen registrations for a lesson.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function reopenRegistration(): void
    {
        $this->checkToken('get');
        $id = $this->input->getInt('id');
        /** @var \CoCoCo\Component\Balancirk\Administrator\Model\LessonModel $model */
        $model = $this->getModel();
        $model->reopenRegistration($id);
        Factory::getApplication()->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_REGISTRATION_REOPENED_NOTICE'), 'success');
        $this->setRedirect(Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . $id, false));
    }

    /**
     * Show cancellation preview / personalization form.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function cancelPreview(): void
    {
        $id = $this->input->getInt('id');
        $this->setRedirect(
            Route::_('index.php?option=com_balancirk&view=lesson&layout=cancel&id=' . $id, false)
        );
    }

    /**
     * Confirm lesson cancellation and send mails.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function confirmCancel(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $id = $this->input->getInt('id');
        $enrolledMessages = array_values((array) $this->input->get('enrolled_messages', [], 'array'));
        $waitingMessages = array_values((array) $this->input->get('waiting_messages', [], 'array'));
        $dismissWaiting = (int) $this->input->getInt('dismiss_waiting', 1) === 1;

        /** @var \CoCoCo\Component\Balancirk\Administrator\Model\LessonModel $model */
        $model = $this->getModel();
        $result = $model->cancelLessonWithMails($id, $enrolledMessages, $waitingMessages, $dismissWaiting);

        if ($result === false) {
            $app->enqueueMessage($model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_balancirk&view=lesson&layout=cancel&id=' . $id, false));

            return;
        }

        $app->enqueueMessage(
            Text::sprintf(
                'COM_BALANCIRK_LESSON_CANCEL_SUCCESS',
                (int) $result['mailed'],
                (int) ($result['unenrolled'] ?? 0),
                (int) $result['dismissed']
            ),
            'success'
        );
        $this->setRedirect(Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . $id, false));
    }
}
