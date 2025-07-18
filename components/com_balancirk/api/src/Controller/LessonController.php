<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Factory;

// {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
class LessonController extends ApiController
{
    protected $contentType = 'lesson'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'lesson'; /* This maps to the folder name containing the JSON API view */

    protected function save($recordKey = null)
    {
        $data = (array) json_decode($this->input->json->getRaw(), true);
        foreach (FieldsHelper::getFields('com_balancirk.lesson') as $field)
        { // This probably looks for a model of the same name
            if (isset($data[$field->name]))
            {
                !isset($data['com_fields']) && $data['com_fields'] = [];
                $data['com_fields'][$field->name] = $data[$field->name];
                unset($data[$field->name]);
            }
        }
        $this->input->set('data', $data);
        return parent::save($recordKey);
    }

    protected function authorizeRequest($task)
    {
        $user = Factory::getApplication()->getIdentity();

        // Block unauthenticated users
        if ($user->guest)
        {
            throw new Exception('Access denied', 401);
        }

        // Role-based permission checks
        switch ($task)
        {
            case 'getLesson':
                if (!$user->authorise('lessons.view', 'com_balancirk'))
                {
                    throw new Exception('Insufficient privileges', 403);
                }
                break;
        }
    }
}
