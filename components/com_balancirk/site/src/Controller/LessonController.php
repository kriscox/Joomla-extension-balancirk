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
use Joomla\CMS\Response\JsonResponse;
use CoCoCo\Component\Balancirk\Site\Model\LessonModel;

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

        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=lessons', false));
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

        $data = $this->input->post->get('jform', [], 'array');
        $lessonId = (int) ($data['id'] ?? 0);
        $data['students'] = isset($data['students']) && is_array($data['students']) ? $data['students'] : [];
        $presenceUrl = Route::_(
            'index.php?option=' . $this->option . '&view=lesson&layout=presence&id=' . $lessonId,
            false
        );
        $lessonUrl = Route::_('index.php?option=' . $this->option . '&view=lesson&id=' . $lessonId, false);

        if ($lessonId <= 0) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_PRESENCE_INVALID_DATE'), 'warning');
            $this->setRedirect($lessonUrl);

            return;
        }

        /** @var LessonModel $model */
        $model = $this->getModel('Lesson');
        $lesson = $model->getItem($lessonId);
        $parsedDate = LessonModel::parseLessonDate($data['date'] ?? null);

        if (
            !$parsedDate instanceof \DateTime
            || !$model->isAttendanceDateAllowed(is_object($lesson) ? $lesson : null, $parsedDate)
        ) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_PRESENCE_INVALID_DATE'), 'warning');
            $this->setRedirect($presenceUrl);

            return;
        }

        $data['id'] = $lessonId;
        $data['date'] = $parsedDate->format('Y-m-d');
        $app->setUserState('com_balancirk.presence.data', $data);

        if (!$model->savePresence($data['id'], $data['date'], $data['students'])) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_PRESENCE_INVALID_DATE'), 'warning');
            $this->setRedirect($presenceUrl);

            return;
        }

        $app->setUserState('com_balancirk.presence.data', null);
        $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_PRESENCE_SAVED'), 'success');
        $this->setRedirect($lessonUrl);
    }

    /**
     * Return present student ids for a lesson date as JSON.
     *
     * @return  void
     *
     * @since   1.3.20
     */
    public function presences(): void
    {
        $this->checkToken('request');

        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest || !$user->authorise('lessons.view', 'com_balancirk')) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $parsedDate = LessonModel::parseLessonDate($this->input->getString('date'));

        /** @var LessonModel $model */
        $model = $this->getModel('Lesson');
        $lesson = $lessonId > 0 ? $model->getItem($lessonId) : null;

        if (
            !$parsedDate instanceof \DateTime
            || !$model->isAttendanceDateAllowed(is_object($lesson) ? $lesson : null, $parsedDate)
        ) {
            echo new JsonResponse(null, Text::_('COM_BALANCIRK_LESSON_PRESENCE_INVALID_DATE'), true);
            $app->close();
        }

        $students = $model->getPresentStudentIds($lessonId, $parsedDate->format('Y-m-d'));
        echo new JsonResponse([
            'students' => $students,
            'hasRecords' => $students !== [],
        ]);
        $app->close();
    }

    /**
     * Save the form data for teachers.
     *
     * @param   string  $key	The name of the key for the primary key.
     * @param   string  $url	The URL to redirect to on success.
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   __BUMP_VERSION__
     */
    public function teacher($key = null, $url = '')
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get the curren application
        /** @var CMSApplication */
        $app = Factory::getApplication();

        $data = $this->input->post->get('jform', [], 'array');
        $lessonId = (int) ($data['id'] ?? 0);
        $data['teachers'] = isset($data['teachers']) && is_array($data['teachers']) ? $data['teachers'] : [];
        $teacherUrl = Route::_(
            'index.php?option=' . $this->option . '&view=lesson&layout=teacher&id=' . $lessonId,
            false
        );
        $lessonUrl = Route::_('index.php?option=' . $this->option . '&view=lesson&id=' . $lessonId, false);

        if ($lessonId <= 0) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_TEACHER_INVALID_DATE'), 'warning');
            $this->setRedirect($lessonUrl);

            return;
        }

        /** @var LessonModel $model */
        $model = $this->getModel('Lesson');
        $lesson = $model->getItem($lessonId);
        $parsedDate = LessonModel::parseLessonDate($data['date'] ?? null);

        if (
            !$parsedDate instanceof \DateTime
            || !$model->isAttendanceDateAllowed(is_object($lesson) ? $lesson : null, $parsedDate)
        ) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_TEACHER_INVALID_DATE'), 'warning');
            $this->setRedirect($teacherUrl);

            return;
        }

        $data['id'] = $lessonId;
        $data['date'] = $parsedDate->format('Y-m-d');
        $app->setUserState('com_balancirk.teacher.data', $data);

        if (!$model->saveTeacher($data['id'], $data['date'], $data['teachers'])) {
            $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_TEACHER_INVALID_DATE'), 'warning');
            $this->setRedirect($teacherUrl);

            return;
        }

        $app->setUserState('com_balancirk.teacher.data', null);
        $app->enqueueMessage(Text::_('COM_BALANCIRK_LESSON_TEACHER_SAVED'), 'success');
        $this->setRedirect($lessonUrl);
    }

    /**
     * Return teacher ids marked as present for a lesson date as JSON.
     *
     * @return  void
     *
     * @since   1.3.20
     */
    public function teachers(): void
    {
        $this->checkToken('request');

        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest || !$user->authorise('lessons.view', 'com_balancirk')) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $parsedDate = LessonModel::parseLessonDate($this->input->getString('date'));

        /** @var LessonModel $model */
        $model = $this->getModel('Lesson');
        $lesson = $lessonId > 0 ? $model->getItem($lessonId) : null;

        if (
            !$parsedDate instanceof \DateTime
            || !$model->isAttendanceDateAllowed(is_object($lesson) ? $lesson : null, $parsedDate)
        ) {
            echo new JsonResponse(null, Text::_('COM_BALANCIRK_LESSON_TEACHER_INVALID_DATE'), true);
            $app->close();
        }

        echo new JsonResponse([
            'teachers' => $model->getTeachedTeacherIds($lessonId, $parsedDate->format('Y-m-d')),
        ]);
        $app->close();
    }
}
