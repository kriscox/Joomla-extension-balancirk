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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use CoCoCo\Component\Balancirk\Site\Model\StudentModel;
use CoCoCo\Component\Balancirk\Site\Model\SubscriptionModel;
use CoCoCo\Component\Balancirk\Site\Model\LessonsModel;

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

        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=subscriptions', false));
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
        $user     = Factory::getApplication()->getIdentity();
        $studentId = (int) ($data['student'] ?? 0);

        if ($studentId <= 0) {
            return false;
        }

        /** @var StudentModel */
        $model    = $this->getModel('Student');

        // Check if the user is the primary parent of the student
        return $model->isPrimairyParent((int) $user->id, $studentId);
    }

    /**
     * Method to check if you can delete a subscription.
     *
     * Primary parents may unsubscribe only when there are at most two
     * attendances. Lesson admins and users with delete permission may always
     * remove a subscription.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   1.6
     */
    protected function allowDelete($data = array())
    {
        return $this->getDeleteDenialReason($data) === null;
    }

    /**
     * Return why the current user may not delete this subscription.
     *
     * @param   array  $data  Student and lesson identifiers.
     *
     * @return  string|null  Denial message, or null when delete is allowed.
     *
     * @since   1.3.20
     */
    private function getDeleteDenialReason(array $data): ?string
    {
        $user = Factory::getApplication()->getIdentity();

        if (
            $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.delete', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        ) {
            return null;
        }

        $studentId = (int) ($data['student'] ?? 0);
        $lessonId = (int) ($data['lesson'] ?? 0);

        if ($studentId <= 0) {
            return Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED');
        }

        /** @var StudentModel $studentModel */
        $studentModel = $this->getModel('Student');

        if (!$studentModel->isPrimairyParent((int) $user->id, $studentId)) {
            return Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED');
        }

        /** @var SubscriptionModel $subscriptionModel */
        $subscriptionModel = $this->getModel();

        if ($subscriptionModel->countPresences($studentId, $lessonId) > 2) {
            return Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETE_TOO_MANY_PRESENCES');
        }

        return null;
    }

    /**
     * Method to check if you can export a list of subscriptions for accounting.
     *
     * Export the list of subscriptions for accounting if the user is an accountant
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   __BUMP_VERSION__
     */
    protected function allowedExport($data = array())
    {
        $user = Factory::getApplication()->getIdentity();

        return $user->authorise('accounting.export', 'com_balancirk');
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
    public function add(?array $key = null)
    {
        // Check if token is correct. Security measure
        $this->checkToken();

        $data = $this->input->get('jform', array(), 'array');

        /** @var SubscriptionModel */
        $model = $this->getModel();

        /** @var CMSApplication */
        $app = Factory::getApplication();
        $app->setUserState('com_balancirk.subscription.data', $data);
        $redirectUrl = Route::_('index.php?option=' . $this->option . '&view=subscription&student_id=' . (int) ($data['student'] ?? 0));

        if ($this->allowAdd($data)) {
            if ($model->add($data)) {
                $app->setUserState('com_balancirk.subscription.data', null);
                $redirectUrl = Route::_('index.php?option=' . $this->option . '&view=subscriptions');
            } else {
                if ($model->getError()) {
                    $app->enqueueMessage($model->getError(), 'warning');
                }
            }
        } else {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'warning');
        }

        $this->setRedirect($redirectUrl);
    }

    /**
     * Return lessons for the selected student as JSON without a page reload.
     *
     * @return  void
     *
     * @since   1.2.12
     */
    public function lessons(): void
    {
        /** @var \Joomla\CMS\Application\CMSApplicationInterface $app */
        $app = Factory::getApplication();
        $studentId = $this->input->getInt('student_id');

        if ($studentId > 0 && !$this->allowAdd(['student' => $studentId])) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), true);
            $app->close();
        }

        /** @var LessonsModel $lessonsModel */
        $lessonsModel = $this->getModel('Lessons');
        $hasOpenLessons = count($lessonsModel->getOpenLessons()) > 0;
        $lessons = $studentId > 0 ? $lessonsModel->getOpenLessons($studentId) : [];
        $data = [];

        foreach ($lessons as $id => $lesson) {
            $data[] = [
                'value' => (int) $id,
                'text' => $lesson,
            ];
        }

        $message = '';

        if (!$hasOpenLessons) {
            $message = Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SUBSCRIPTION');
        } elseif ($studentId <= 0) {
            $message = Text::_('COM_BALANCIRK_SELECT_STUDENT_FOR_LESSONS');
        } elseif (empty($data)) {
            $message = Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SELECTED_STUDENT');
        }

        echo new JsonResponse([
            'lessons' => $data,
            'message' => $message,
            'hasOpenLessons' => $hasOpenLessons,
        ]);
        $app->close();
    }

    /**
     * Delete subscription
     *
     * Delete the subscription for the lesson and the student
     *
     * @param	array	$key	List of fields of the form
     *
     * @return	void
     *
     * @version __BUMP_VERSION__
     **/
    public function delete(?array $key = null)
    {
        $this->checkToken();

        $wantsJson = $this->input->getCmd('format') === 'json';
        /** @var SubscriptionModel */
        $model = $this->getModel();
        $subscriptionId = $this->input->getInt('id');
        $data = $this->input->get('jform', array(), 'array');

        if ($subscriptionId > 0) {
            $record = $model->getSubscriptionRecord($subscriptionId);

            if (!$record) {
                $this->sendDeleteResult($wantsJson, false, Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETE_NOT_FOUND'));

                return;
            }

            $data['id'] = (int) $record->id;
            $data['student'] = (int) $record->student;
            $data['lesson'] = (int) $record->lesson;
        }

        $denialReason = $this->getDeleteDenialReason($data);

        if ($denialReason !== null) {
            $this->sendDeleteResult($wantsJson, false, $denialReason);

            return;
        }

        $pk = $subscriptionId > 0 ? $subscriptionId : $data;

        if (!$model->delete($pk)) {
            $this->sendDeleteResult(
                $wantsJson,
                false,
                $model->getError() ?: Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETE_FAILED')
            );

            return;
        }

        $this->sendDeleteResult(
            $wantsJson,
            true,
            Text::_('COM_BALANCIRK_SUBSCRIPTION_DELETED'),
            $subscriptionId
        );
    }

    /**
     * Return a JSON or redirect response after a delete attempt.
     *
     * @param   bool    $wantsJson  Whether the client asked for JSON.
     * @param   bool    $success    Whether the delete succeeded.
     * @param   string  $message    Message to show.
     * @param   int     $id         Deleted subscription id.
     *
     * @return  void
     *
     * @since   1.3.20
     */
    private function sendDeleteResult(bool $wantsJson, bool $success, string $message, int $id = 0): void
    {
        $app = Factory::getApplication();

        if ($wantsJson) {
            echo new JsonResponse($success ? ['id' => $id] : null, $message, !$success);
            $app->close();
        }

        $app->enqueueMessage($message, $success ? 'success' : 'warning');
        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=subscriptions', false));
    }

    /**
     * Export Subscriptions
     *
     * Method to export a list of subscriptions for accounting
     *
     * @return  void
     *
     * @since   __BUMP_VERSION__
     **/
    public function export(): void
    {
        Session::checkToken('get');

        $app = Factory::getApplication();
        $year = $this->input->getString('year');
        $format = $this->input->getCmd('format', 'csv');

        /** @var SubscriptionModel */
        $model = $this->getModel();

        if (!$this->allowedExport()) {
            $this->setRedirect(
                Route::_('index.php?option=' . $this->option . '&view=subscriptions', false),
                Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'),
                'warning'
            );

            return;
        }

        $export = $model->exportForAccounting($year, $format);

        $app->setHeader('Content-Type', $export['mimeType'], true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $export['filename'] . '"', true);
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $app->sendHeaders();

        echo $export['content'];
        $app->close();
    }
}
