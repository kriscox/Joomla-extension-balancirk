<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

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
        $data = (array) json_decode($this->input->json->getRaw(), true);
        foreach (FieldsHelper::getFields('com_balancirk.student') as $field)
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
     * Basic display of a list view
     *
     * @return  static  A \JControllerLegacy object to support chaining.
     *
     * @since   4.0.0
     */
    public function displayList()
    {
        /** @var model studentsmodel */
        //$model = $this->getModel($this->contentType);
        //$items = $model->getItems();

        // Process and return the items
        return parent::displayList();
    }
}
