<?php
  namespace CoCoCo\Component\Balancirk\Api\Controller;

  defined('_JEXEC') or die;

  use Joomla\CMS\MVC\Controller\ApiController;
  use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

  // {controllerName} here is merely a placeholder for the shared classnaming system across controllers, view folders (and possibly models)
  class StudentController extends ApiController
  {
    protected $contentType = 'student'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'student'; /* This maps to the folder name containing the JSON API view */

    protected function save($recordKey = null)
    {
      $data = (array) json_decode($this->input->json->getRaw(), true);
      foreach (FieldsHelper::getFields('com_balancirk.student') as $field) // This probably looks for a model of the same name
      {
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
  }
            