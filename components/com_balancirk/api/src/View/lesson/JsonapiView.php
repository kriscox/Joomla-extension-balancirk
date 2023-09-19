<?php
  namespace CoCoCo\Component\Balancirk\Api\View\Lesson;

  defined('_JEXEC') or die;

  use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
  use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

  class JsonapiView extends BaseApiView
  {
    protected $fieldsToRenderItem = ['id', 'alias', 'name', 'catid'];
    protected $fieldsToRenderList = ['id', 'alias', 'name', 'catid'];

    public function displayList(array $items = null)
    {
      foreach (FieldsHelper::getFields('com_balancirk.lesson') as $field)
      {
        $this->fieldsToRenderList[] = $field->id;
      }
      return parent::displayList();
    }

    public function displayItem($item = null)
    {
      foreach (FieldsHelper::getFields('com_balancirk.lesson') as $field)
      {
        $this->fieldsToRenderItem[] = $field->name;
      }
      return parent::displayItem();
    }

    protected function prepareItem($item)
    {
      foreach (FieldsHelper::getFields('com_balancirk.lesson', $item, true) as $field)
      {
        $item->{$field->name} = isset($field->apivalue) ? $field->apivalue : $field->rawvalue;
      }
      return parent::prepareItem($item);
    }
  }
          