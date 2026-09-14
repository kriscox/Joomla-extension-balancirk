<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use CoCoCo\Component\Balancirk\Site\Model\MemberModel as SiteMemberModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;

// {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
class MembersController extends ApiController
{
    protected $contentType = 'members'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'members'; /* This maps to the folder name containing the JSON API view */

    protected function save($recordKey = null)
    {
        $data = $this->getRequestData();
        $this->mapCustomFields($data, 'com_balancirk.members');
        $recordKey = (int) ($recordKey ?? ($data['id'] ?? $this->input->getInt('id')));

        if ($recordKey <= 0) {
            if (!$this->canCreateMember()) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
            }

            $this->input->set('data', $data);

            return parent::save(null);
        }

        if ($this->isCurrentUser($recordKey)) {
            return $this->saveOwnProfile($data, $recordKey);
        }

        if (!$this->canEditAnyMember()) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
        }

        $data['id'] = $recordKey;
        $this->input->set('data', $data);

        return parent::save($recordKey);
    }

    /**
     * Public member registration endpoint.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function register(): void
    {
        $app = Factory::getApplication();
        $data = $this->getRequestData();

        try
        {
            $model = $this->getSiteMemberModel();
            $form = $model->getForm($data, false);

            if (!$form) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
            }

            $validData = $model->validate($form, $data);

            if ($validData === false) {
                throw new \RuntimeException($this->getValidationErrorMessage($model->getErrors()), 400);
            }

            if (!$model->register($validData)) {
                throw new \RuntimeException($model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), 400);
            }

            echo new JsonResponse([
                'registered' => true,
                'message' => Text::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'),
            ]);
        }
        catch (\RuntimeException $e)
        {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->close();
    }

    /**
     * Update the current member profile.
     *
     * @return  \Joomla\CMS\Webservice\Response\ResponseInterface
     *
     * @since   1.2.29
     */
    public function updateme()
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
        }

        $data = $this->getRequestData();

        return $this->saveOwnProfile($data, (int) $user->id);
    }

    /**
     * Method to get the current user data.
     *
     * @return  \Joomla\CMS\Webservice\Response\ResponseInterface
     */
    public function getCurrentUser()
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
        }

        return $this->displayItem($id = (int) $user->id);
    }

    /**
     * Return only the students linked to the current member.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function getmystudents(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.id'),
                $db->quoteName('s.firstname'),
                $db->quoteName('s.name'),
                $db->quoteName('s.birthdate'),
                $db->quoteName('s.mutuality'),
                $db->quoteName('s.uitpas'),
            ])
            ->from($db->quoteName('#__balancirk_students', 's'))
            ->join('INNER', $db->quoteName('#__balancirk_parents', 'p') . ' ON ' . $db->quoteName('p.child') . ' = ' . $db->quoteName('s.id'))
            ->where($db->quoteName('p.parent') . ' = ' . (int) $user->id)
            ->order($db->quoteName('s.firstname') . ' ASC')
            ->order($db->quoteName('s.name') . ' ASC');
        $db->setQuery($query);

        echo new JsonResponse($db->loadAssocList() ?: []);
        $app->close();
    }

    /**
     * Return parent-student relation rows for admin/accounting users.
     *
     * @return  void
     *
     * @since   1.2.33
     */
    public function getrelations(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest || !$this->canViewRelations()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id', 'id'),
                $db->quoteName('p.parent', 'parent_id'),
                $db->quoteName('m.firstname', 'parent_firstname'),
                $db->quoteName('m.name', 'parent_name'),
                $db->quoteName('m.email', 'parent_email'),
                $db->quoteName('p.child', 'student_id'),
                $db->quoteName('s.firstname', 'student_firstname'),
                $db->quoteName('s.name', 'student_name'),
                $db->quoteName('p.primary', 'is_primary'),
            ])
            ->from($db->quoteName('#__balancirk_parents', 'p'))
            ->join('INNER', $db->quoteName('#__balancirk_members', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('p.parent'))
            ->join('INNER', $db->quoteName('#__balancirk_students', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.child'))
            ->order($db->quoteName('m.name') . ' ASC')
            ->order($db->quoteName('m.firstname') . ' ASC')
            ->order($db->quoteName('s.name') . ' ASC')
            ->order($db->quoteName('s.firstname') . ' ASC');
        $db->setQuery($query);

        echo new JsonResponse($db->loadAssocList() ?: []);
        $app->close();
    }

    /**
     * Save profile data for the currently authenticated member.
     *
     * @param   array  $data      Request payload.
     * @param   int    $memberId  Member id.
     *
     * @return  \Joomla\CMS\Webservice\Response\ResponseInterface
     *
     * @since   1.2.29
     */
    private function saveOwnProfile(array $data, int $memberId)
    {
        if ($memberId <= 0) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
        }

        $model = $this->getSiteMemberModel();
        $data['id'] = $memberId;
        $form = $model->getForm($data, false);

        if (!$form) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        $validData = $model->validate($form, $data);

        if ($validData === false) {
            throw new \RuntimeException($this->getValidationErrorMessage($model->getErrors()), 400);
        }

        $validData['id'] = $memberId;

        if (!$model->edit($validData)) {
            throw new \RuntimeException($model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), 400);
        }

        return $this->displayItem($memberId);
    }

    /**
     * Build a readable validation message.
     *
     * @param   array  $errors  Validation errors.
     *
     * @return  string
     *
     * @since   1.2.29
     */
    private function getValidationErrorMessage(array $errors): string
    {
        $messages = [];

        foreach ($errors as $error)
        {
            $messages[] = $error instanceof \Throwable ? $error->getMessage() : (string) $error;
        }

        $messages = array_values(array_filter(array_map('trim', $messages), static fn(string $message): bool => $message !== ''));

        if (empty($messages)) {
            return Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED');
        }

        return implode(' ', $messages);
    }

    /**
     * Check create rights for member CRUD endpoint.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canCreateMember(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && ($user->authorise('core.create', 'com_balancirk') || $user->authorise('core.admin', 'com_balancirk'));
    }

    /**
     * Check elevated edit rights for members.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canEditAnyMember(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && ($user->authorise('core.edit', 'com_balancirk') || $user->authorise('core.admin', 'com_balancirk'));
    }

    /**
     * Check if user may view parent-student relations.
     *
     * @return  bool
     *
     * @since   1.2.33
     */
    private function canViewRelations(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && (
            $user->authorise('accounting.viewrelations', 'com_balancirk')
            || $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        );
    }

    /**
     * Check if record belongs to current user.
     *
     * @param   int  $memberId  Member id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function isCurrentUser(int $memberId): bool
    {
        return (int) Factory::getApplication()->getIdentity()->id === $memberId;
    }

    /**
     * Decode request body and support JSON:API payloads.
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

    /**
     * Create a site member model instance.
     *
     * @return  SiteMemberModel
     *
     * @since   1.2.29
     */
    private function getSiteMemberModel(): SiteMemberModel
    {
        $model = $this->getMVCFactory()->createModel('Member', 'Site', ['ignore_request' => true]);

        if (!$model instanceof SiteMemberModel) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        return $model;
    }
}
