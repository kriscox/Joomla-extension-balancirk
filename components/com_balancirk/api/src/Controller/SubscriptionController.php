<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

defined('_JEXEC') or die;

use Joomla\String\Inflector;
use PHP_CodeSniffer\Generators\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;

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
        $this->input->set('data', $data);
        return parent::save($recordKey);
    }

    public function delete($recordKey = null)
    {
        $recordKey = $this->input->get('id');

        $modelName = $this->input->get('model', Inflector::singularize($this->contentType));

        /** @var \Joomla\CMS\MVC\Model\AdminModel $model */
        $model = $this->getModel($modelName, '', ['ignore_request' => true]);

        if (!$model)
        {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        if (!$model->getItem($recordKey))
            throw new ResourceNotFound;

        return $model->delete($recordKey);
    }
}
