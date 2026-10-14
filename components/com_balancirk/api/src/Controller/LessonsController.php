<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use CoCoCo\Component\Balancirk\Administrator\Model\LessonModel;

/**
 * Lessons API controller including waitlist settlement and cancellation.
 *
 * @since  0.0.1
 */
class LessonsController extends ApiController
{
    protected $contentType = 'lessons';
    protected $default_view = 'lessons';

    protected function save($recordKey = null)
    {
        $data = (array) json_decode($this->input->json->getRaw(), true);

        foreach (FieldsHelper::getFields('com_balancirk.lesson') as $field) {
            if (isset($data[$field->name])) {
                !isset($data['com_fields']) && $data['com_fields'] = [];
                $data['com_fields'][$field->name] = $data[$field->name];
                unset($data[$field->name]);
            }
        }

        $this->input->set('data', $data);

        return parent::save($recordKey);
    }

    /**
     * Method to get the current user data.
     *
     * @return  mixed
     */
    public function getCurrentUser()
    {
        $user = Factory::getApplication()->getIdentity();

        return $this->displayItem($user->id);
    }

    /**
     * GET waitlist in FIFO order.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function getWaitlist(): void
    {
        $app = Factory::getApplication();

        if (!$this->canManageLessons()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $model = $this->getLessonModel();
        $rows = $model->getWaitlistOrdered($lessonId);

        echo new JsonResponse([
            'lesson_id' => $lessonId,
            'waitlist' => array_map(static function ($row) {
                return [
                    'subscription_id' => (int) $row->id,
                    'student_id' => (int) $row->student,
                    'firstname' => (string) ($row->firstname ?? ''),
                    'name' => (string) ($row->name ?? ''),
                    'birthdate' => (string) ($row->birthdate ?? ''),
                    'fifo_rank' => (int) ($row->fifo_rank ?? 0),
                ];
            }, $rows),
        ]);
        $app->close();
    }

    /**
     * POST settle waitlist.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function settleWaitlist(): void
    {
        $app = Factory::getApplication();

        if (!$this->canManageLessons()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $payload = $this->getRequestData();
        $model = $this->getLessonModel();
        $result = $model->settleWaitlist(
            $lessonId,
            (array) ($payload['promote_ids'] ?? []),
            (array) ($payload['dismiss_ids'] ?? []),
            !empty($payload['confirm_fifo_override']),
            array_key_exists('close_registration', $payload) ? (bool) $payload['close_registration'] : true
        );

        if ($result === false) {
            echo new JsonResponse(null, $model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), true);
            $app->close();
        }

        echo new JsonResponse($result);
        $app->close();
    }

    /**
     * POST close registration.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function closeRegistration(): void
    {
        $this->toggleRegistration(true);
    }

    /**
     * POST reopen registration.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function reopenRegistration(): void
    {
        $this->toggleRegistration(false);
    }

    /**
     * GET cancellation preview.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function previewCancellation(): void
    {
        $app = Factory::getApplication();

        if (!$this->canManageLessons()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $model = $this->getLessonModel();
        $preview = $model->getCancellationPreview($lessonId);

        if ($preview === false) {
            echo new JsonResponse(null, $model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), true);
            $app->close();
        }

        echo new JsonResponse([
            'lesson_id' => $lessonId,
            'lesson_name' => (string) ($preview['lesson']->name ?? ''),
            'enrolled' => $preview['enrolled'],
            'waiting' => $preview['waiting'],
        ]);
        $app->close();
    }

    /**
     * POST cancel lesson with personalized mails.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    public function cancelLesson(): void
    {
        $app = Factory::getApplication();

        if (!$this->canManageLessons()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $payload = $this->getRequestData();
        $model = $this->getLessonModel();
        $result = $model->cancelLessonWithMails(
            $lessonId,
            (array) ($payload['messages'] ?? $payload['enrolled_messages'] ?? []),
            (array) ($payload['waiting_messages'] ?? []),
            array_key_exists('dismiss_waiting', $payload) ? (bool) $payload['dismiss_waiting'] : true
        );

        if ($result === false) {
            echo new JsonResponse(null, $model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), true);
            $app->close();
        }

        echo new JsonResponse($result);
        $app->close();
    }

    /**
     * @param   bool  $close  Close or reopen.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    private function toggleRegistration(bool $close): void
    {
        $app = Factory::getApplication();

        if (!$this->canManageLessons()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lessonId = $this->input->getInt('id');
        $model = $this->getLessonModel();
        $ok = $close ? $model->closeRegistration($lessonId) : $model->reopenRegistration($lessonId);

        echo new JsonResponse([
            'lesson_id' => $lessonId,
            'registration_closed' => $close,
            'updated' => $ok,
        ]);
        $app->close();
    }

    /**
     * @return  LessonModel
     *
     * @since   1.3.25
     */
    private function getLessonModel(): LessonModel
    {
        return $this->getModel('Lesson', 'Administrator');
    }

    /**
     * @return  array
     *
     * @since   1.3.25
     */
    private function getRequestData(): array
    {
        $payload = (array) json_decode((string) $this->input->json->getRaw(), true);

        if (isset($payload['data']) && \is_array($payload['data'])) {
            $payloadData = $payload['data'];

            return isset($payloadData['attributes']) && \is_array($payloadData['attributes'])
                ? $payloadData['attributes']
                : $payloadData;
        }

        return $payload;
    }

    /**
     * @return  bool
     *
     * @since   1.3.25
     */
    private function canManageLessons(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest
            && (
                $user->authorise('core.manage', 'com_balancirk')
                || $user->authorise('lessons.admin', 'com_balancirk')
                || $user->authorise('core.admin', 'com_balancirk')
            );
    }
}
