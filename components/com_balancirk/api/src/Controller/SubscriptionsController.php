<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use DateTimeImmutable;
use CoCoCo\Component\Balancirk\Administrator\Model\LessonsModel;
use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;

// {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
/** @package CoCoCo\Component\Balancirk\Api\Controller */
class SubscriptionsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  __BUMPER_VERSION__
     */
    protected $contentType = 'subscriptions'; /* My understanding is that this maps to the desired model name */
    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  __BUMPER_VERSION__
     */
    protected $default_view = 'subscriptions'; /* This maps to the folder name containing the JSON API view */

    /**
     * Override list display to scope results to the current user.
     *
     * Admins have the `students.viewall` permission which normally bypasses the
     * parent filter in SubscriptionsModel. For the member-portal API the list
     * must always be limited to the requesting user's own children.
     *
     * @param   mixed  $data  Unused.
     *
     * @return  mixed
     *
     * @since   1.3.5
     */
    public function displayList($data = null)
    {
        $model = $this->getModel();

        if ($model) {
            $model->setState('filter.parent_id', (int) Factory::getApplication()->getIdentity()->id);
        }

        return parent::displayList($data);
    }

    protected function save($recordKey = null)
    {
        $data = $this->getRequestData();
        $this->mapCustomFields($data, 'com_balancirk.subscription');
        $recordKey = $recordKey ?? ($data['id'] ?? null);

        // New subscriptions must use the same flow as the website:
        // age checks, waiting-list logic and automatic emails.
        if (empty($recordKey)) {
            if (!$this->canCreateSubscription($data)) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
            }

            return $this->createSubscription($data);
        }

        if (!$this->canUpdateSubscription((int) $recordKey)) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
        }

        $this->input->set('data', $data);

        return parent::save((int) $recordKey);
    }

    /**
     * Return lessons open for registration for a selected student.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function openlessons(): void
    {
        $app = Factory::getApplication();
        $studentId = $this->input->getInt('student', $this->input->getInt('student_id', 0));

        if ($studentId <= 0) {
            echo new JsonResponse([
                'lessons' => [],
                'message' => Text::_('COM_BALANCIRK_SELECT_STUDENT_FOR_LESSONS'),
                'hasOpenLessons' => false,
            ]);
            $app->close();
        }

        if (!$this->canCreateSubscription(['student' => $studentId])) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), true);
            $app->close();
        }

        $date = $this->normalizeDate($this->input->getString('date'));

        /** @var LessonsModel $lessonsModel */
        $lessonsModel = $this->getModel('Lessons');

        if (!$lessonsModel) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'), true);
            $app->close();
        }

        $allOpenLessons = $lessonsModel->getOpenLessons(null, $date);
        $filteredLessons = $lessonsModel->getOpenLessons($studentId, $date);

        $data = [];

        foreach ($filteredLessons as $id => $name)
        {
            $data[] = [
                'value' => (int) $id,
                'text' => $name,
            ];
        }

        $message = '';

        if (count($allOpenLessons) === 0) {
            $message = Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SUBSCRIPTION');
        } elseif (empty($data)) {
            $message = Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SELECTED_STUDENT');
        }

        echo new JsonResponse([
            'lessons' => $data,
            'message' => $message,
            'hasOpenLessons' => count($allOpenLessons) > 0,
        ]);
        $app->close();
    }

    /**
     * Export subscription data for accounting.
     * Default output is JSON rows; use format=csv or format=xls for downloadable files.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function accountexport(): void
    {
        $app = Factory::getApplication();

        if (!$this->canExportAccounting()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $year = $this->input->getString('year');
        $yearFilter = $year !== '' ? $year : null;
        $format = strtolower($this->input->getCmd('format', 'json'));

        /** @var SubscriptionModel $model */
        $model = $this->getModel('Subscription');

        if (!$model) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'), true);
            $app->close();
        }

        try
        {
            if (\in_array($format, ['csv', 'xls'], true))
            {
                $export = $model->exportForAccounting($yearFilter, $format);
                $app->setHeader('Content-Type', $export['mimeType'], true);
                $app->setHeader('Content-Disposition', 'attachment; filename="' . $export['filename'] . '"', true);
                $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
                $app->sendHeaders();
                echo $export['content'];
                $app->close();
            }

            echo new JsonResponse([
                'year' => $yearFilter,
                'rows' => $model->getAccountingExportRows($yearFilter),
            ]);
        }
        catch (\RuntimeException $e)
        {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->close();
    }

    /**
     * Create a subscription using model::add() so business rules are enforced.
     *
     * @param   array  $data  Subscription payload.
     *
     * @return  \Joomla\CMS\Webservice\Response\Response
     *
     * @since   1.2.29
     */
    private function createSubscription(array $data)
    {
        /** @var SubscriptionModel $model */
        $model = $this->getModel('Subscription');

        if (!$model) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        try
        {
            $created = $model->add($data);
        }
        catch (\Throwable $e)
        {
            throw new \RuntimeException($model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), 400, $e);
        }

        if (!$created) {
            throw new \RuntimeException($model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), 400);
        }

        $id = $model->getLastInsertedId();

        if ($id <= 0) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'));
        }

        return $this->displayItem($id);
    }

    /**
     * Normalize custom fields into com_fields payload.
     *
     * @param   array   $data     Request payload.
     * @param   string  $context  Fields context.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    private function mapCustomFields(array &$data, string $context): void
    {
        foreach (FieldsHelper::getFields($context) as $field)
        {
            if (!isset($data[$field->name])) {
                continue;
            }

            if (!isset($data['com_fields']) || !\is_array($data['com_fields'])) {
                $data['com_fields'] = [];
            }

            $data['com_fields'][$field->name] = $data[$field->name];
            unset($data[$field->name]);
        }
    }

    /**
     * Decode request body and support both flat JSON and JSON:API payloads.
     *
     * @return  array
     *
     * @since   1.2.29
     */
    private function getRequestData(): array
    {
        $payload = (array) json_decode((string) $this->input->json->getRaw(), true);

        if (isset($payload['data']) && \is_array($payload['data'])) {
            $payloadData = $payload['data'];
            $attributes = isset($payloadData['attributes']) && \is_array($payloadData['attributes'])
                ? $payloadData['attributes']
                : [];

            if (!empty($payloadData['id'])) {
                $attributes['id'] = (int) $payloadData['id'];
            }

            return $attributes;
        }

        return $payload;
    }

    /**
     * Check whether the current user may create subscriptions for the student.
     *
     * @param   int  $studentId  Student id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canAccessStudent(int $studentId): bool
    {
        if ($studentId <= 0) {
            return false;
        }

        $user = Factory::getApplication()->getIdentity();

        if ($this->canManageAllStudents()) {
            return true;
        }

        return $this->isPrimaryParent((int) $user->id, $studentId);
    }

    /**
     * Normalize date query parameter to Y-m-d.
     *
     * @param   string|null  $date  Input date.
     *
     * @return  string
     *
     * @since   1.2.29
     */
    private function normalizeDate(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return date('Y-m-d');
        }

        $normalized = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($normalized instanceof DateTimeImmutable) {
            return $normalized->format('Y-m-d');
        }

        return date('Y-m-d');
    }

    /**
     * Check whether the current user may create a subscription for a student.
     *
     * @param   array  $data  Incoming payload.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canCreateSubscription(array $data): bool
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            return false;
        }

        $studentId = (int) ($data['student'] ?? 0);

        return $this->canAccessStudent($studentId);
    }

    /**
     * Only elevated users may update existing subscriptions via API.
     *
     * @param   int  $recordKey  Subscription id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canUpdateSubscription(int $recordKey): bool
    {
        if ($recordKey <= 0 || !Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_balancirk')) {
            return false;
        }

        /** @var SubscriptionModel $model */
        $model = $this->getModel('Subscription');

        if (!$model) {
            return false;
        }

        $item = $model->getItem($recordKey);

        if (!$item || (int) ($item->id ?? 0) <= 0) {
            throw new ResourceNotFound();
        }

        return $this->canManageAllStudents();
    }

    /**
     * Whether the current user may export accounting data.
     *
     * @return  bool
     *
     * @since   1.2.33
     */
    private function canExportAccounting(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && (
            $user->authorise('accounting.export', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        );
    }

    /**
     * Whether the current user has global management rights.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canManageAllStudents(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk');
    }

    /**
     * Check if a user is registered as primary parent for a student.
     *
     * @param   int  $userId     User id.
     * @param   int  $studentId  Student id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function isPrimaryParent(int $userId, int $studentId): bool
    {
        if ($userId <= 0 || $studentId <= 0) {
            return false;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__balancirk_parents', 'p'))
            ->where($db->quoteName('p.child') . ' = ' . (int) $studentId)
            ->where($db->quoteName('p.parent') . ' = ' . (int) $userId)
            ->where($db->quoteName('p.primary') . ' = 1');

        $db->setQuery($query);

        return (bool) $db->loadResult();
    }
}
