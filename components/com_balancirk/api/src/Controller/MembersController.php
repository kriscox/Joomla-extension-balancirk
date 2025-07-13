<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

// {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
class MembersController extends ApiController
{
    protected $contentType = 'members'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'members'; /* This maps to the folder name containing the JSON API view */

    protected function save($recordKey = null)
    {
        $data = (array) json_decode($this->input->json->getRaw(), true);
        foreach (FieldsHelper::getFields('com_balancirk.members') as $field)
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

    /**
     * Method to get the current user data.
     *
     * @return  \Joomla\CMS\Webservice\Response\ResponseInterface
     */
    public function getCurrentUser()
    {
        $user = Factory::getApplication()->getIdentity();

        return $this->displayItem($id = $user->id);
    }
}
