<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;

// {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
/** @package CoCoCo\Component\Balancirk\Api\Controller */
class StudentsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  __BUMPER_VERSION__
     */
    protected $contentType = 'students'; /* My understanding is that this maps to the desired model name */
    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  __BUMPER_VERSION__
     */
    protected $default_view = 'students'; /* This maps to the folder name containing the JSON API view */

    protected function save($recordKey = null)
    {
        $data = $this->getRequestData();
        $this->mapCustomFields($data, 'com_balancirk.student');
        $recordKey = (int) ($recordKey ?? ($data['id'] ?? $this->input->getInt('id')));
        $isNew = $recordKey <= 0;

        if ($isNew) {
            if (!$this->canCreateStudent()) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
            }

            // Keep the same default as the site form flow for new students.
            if (!isset($data['state'])) {
                $data['state'] = 1;
            }
        } else {
            if (!$this->canUpdateStudent($recordKey)) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
            }

            $data['id'] = $recordKey;
        }

        $this->input->set('data', $data);
        $response = parent::save($isNew ? null : $recordKey);

        if ($isNew) {
            $model = $this->getModel('Student');
            $studentId = $model ? (int) $model->getState('student.id') : 0;

            if ($studentId > 0 && $this->shouldAutoLinkPrimaryParent()) {
                $this->ensurePrimaryParentLink($studentId, (int) Factory::getApplication()->getIdentity()->id);
            }
        }

        return $response;
    }

    /**
     * Check create permissions.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canCreateStudent(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && (
            $user->authorise('core.create', 'com_balancirk')
            || $user->authorise('lessons.register', 'com_balancirk')
            || $user->authorise('students.editall', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        );
    }

    /**
     * Check update permissions for a student.
     *
     * @param   int  $studentId  Student id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canUpdateStudent(int $studentId): bool
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest || $studentId <= 0) {
            return false;
        }

        if (
            $user->authorise('core.edit', 'com_balancirk')
            || $user->authorise('students.editall', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        ) {
            return true;
        }

        return $this->isParentOfStudent((int) $user->id, $studentId);
    }

    /**
     * Check if user is linked as parent to a student.
     *
     * @param   int  $userId     User id.
     * @param   int  $studentId  Student id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function isParentOfStudent(int $userId, int $studentId): bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__balancirk_parents', 'p'))
            ->where($db->quoteName('p.parent') . ' = ' . $userId)
            ->where($db->quoteName('p.child') . ' = ' . $studentId);
        $db->setQuery($query);

        return (bool) $db->loadResult();
    }

    /**
     * Determine if a newly created student should be linked to current user as primary parent.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function shouldAutoLinkPrimaryParent(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            return false;
        }

        return !(
            $user->authorise('students.editall', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
            || $user->authorise('accounting.viewrelations', 'com_balancirk')
        );
    }

    /**
     * Ensure the creating member is linked as primary parent for a new student.
     *
     * @param   int  $studentId  Student id.
     * @param   int  $parentId   Parent/member id.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    private function ensurePrimaryParentLink(int $studentId, int $parentId): void
    {
        if ($studentId <= 0 || $parentId <= 0) {
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__balancirk_parents'))
            ->where($db->quoteName('child') . ' = ' . $studentId)
            ->where($db->quoteName('parent') . ' = ' . $parentId);
        $db->setQuery($query);
        $existing = (int) $db->loadResult();

        if ($existing > 0) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__balancirk_parents'))
                ->set($db->quoteName('primary') . ' = 1')
                ->where($db->quoteName('id') . ' = ' . $existing);
            $db->setQuery($query)->execute();

            return;
        }

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__balancirk_parents'))
            ->columns($db->quoteName(['child', 'parent', 'primary']))
            ->values($studentId . ', ' . $parentId . ', 1');
        $db->setQuery($query)->execute();
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
}
