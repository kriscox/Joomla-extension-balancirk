<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\String\Inflector;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;

// {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
class SubscriptionController extends ApiController
{
    protected $contentType = 'subscription'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'subscription'; /* This maps to the folder name containing the JSON API view */

    protected function save($recordKey = null)
    {
        $data = (array) json_decode($this->input->json->getRaw(), true);

        foreach (FieldsHelper::getFields('com_balancirk.subscription') as $field)
        { // This probably looks for a model of the same name
            if (isset($data[$field->name]))
            {
                !isset($data['com_fields']) && $data['com_fields'] = [];
                $data['com_fields'][$field->name] = $data[$field->name];
                unset($data[$field->name]);
            }
        }

        $recordKey = (int) ($recordKey ?? ($data['id'] ?? $this->input->getInt('id')));

        if ($recordKey <= 0 || !$this->canUpdateSubscription($recordKey))
        {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
        }

        $this->input->set('data', $data);

        return parent::save($recordKey);
    }

    public function delete($recordKey = null)
    {
        $recordKey = $this->input->get('id');

        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.delete', 'com_balancirk')) {
            throw new \Joomla\CMS\Access\Exception\NotAllowed(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
        }

        $modelName = $this->input->get('model', Inflector::singularize($this->contentType));

        /** @var \Joomla\CMS\MVC\Model\AdminModel $model */
        $model = $this->getModel('Subscription', '', ['ignore_request' => true]);

        if (!$model)
        {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        $item = $model->getItem($recordKey);

        if (!$item || (int) ($item->id ?? 0) <= 0)
        {
            throw new ResourceNotFound();
        }

        if (!$this->canDeleteSubscription((int) $item->student, (int) $item->lesson, (int) $item->id))
        {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 403);
        }

        return $model->delete($recordKey);
    }

    /**
     * Validate update permissions on a subscription record.
     *
     * @param   int  $recordKey  Subscription id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canUpdateSubscription(int $recordKey): bool
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest || !$user->authorise('core.edit', 'com_balancirk'))
        {
            return false;
        }

        /** @var \Joomla\CMS\MVC\Model\AdminModel $model */
        $model = $this->getModel('Subscription', '', ['ignore_request' => true]);

        if (!$model)
        {
            return false;
        }

        $item = $model->getItem($recordKey);

        if (!$item || (int) ($item->id ?? 0) <= 0)
        {
            throw new ResourceNotFound();
        }

        return $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk');
    }

    /**
     * Check if current user may delete the subscription.
     *
     * @param   int  $studentId       Student id.
     * @param   int  $lessonId        Lesson id.
     * @param   int  $subscriptionId  Subscription id.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canDeleteSubscription(int $studentId, int $lessonId, int $subscriptionId): bool
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest || $studentId <= 0 || $lessonId <= 0 || $subscriptionId <= 0)
        {
            return false;
        }

        if (
            $user->authorise('students.viewall', 'com_balancirk')
            || $user->authorise('lessons.admin', 'com_balancirk')
            || $user->authorise('core.delete', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        ) {
            return true;
        }

        return $this->isPrimaryParent((int) $user->id, $studentId) && $this->presenceCount($studentId, $lessonId) <= 2;
    }

    /**
     * Check if a user is primary parent for a student.
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
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__balancirk_parents', 'p'))
            ->where($db->quoteName('p.parent') . ' = ' . $userId)
            ->where($db->quoteName('p.child') . ' = ' . $studentId)
            ->where($db->quoteName('p.primary') . ' = 1');
        $db->setQuery($query);

        return (bool) $db->loadResult();
    }

    /**
     * Count presences for a student in a lesson.
     *
     * @param   int  $studentId  Student id.
     * @param   int  $lessonId   Lesson id.
     *
     * @return  int
     *
     * @since   1.2.29
     */
    private function presenceCount(int $studentId, int $lessonId): int
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__balancirk_presences', 'p'))
            ->where($db->quoteName('p.student') . ' = ' . $studentId)
            ->where($db->quoteName('p.lesson') . ' = ' . $lessonId);
        $db->setQuery($query);

        return (int) $db->loadResult();
    }
}
