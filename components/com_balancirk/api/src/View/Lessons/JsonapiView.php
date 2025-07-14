<?php

namespace CoCoCo\Component\Balancirk\Api\View\Members;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class JsonapiView extends BaseApiView
{
    protected $fieldsToRenderItem = [
        'id',
        'name',
        'type',
        'fee',
        'year',
        'start',
        'end',
        'start_registration',
        'end_registration',
    ];

    protected $fieldsToRenderList = [
        'id',
        'name',
        'type',
        'year',
        'start',
        'end',
    ];

    public function displayList(array $items = null)
    {
        foreach (FieldsHelper::getFields('com_balancirk.lessons') as $field)
        {
            $this->fieldsToRenderList[] = $field->id;
        }
        return parent::displayList();
    }

    public function displayItem($item = null)
    {
        foreach (FieldsHelper::getFields('com_balancirk.lessons') as $field)
        {
            $this->fieldsToRenderItem[] = $field->name;
        }
        return parent::displayItem();
    }

    protected function prepareItem($item)
    {
        foreach (FieldsHelper::getFields('com_balancirk.lessons', $item, true) as $field)
        {
            $item->{$field->name} = isset($field->apivalue) ? $field->apivalue : $field->rawvalue;
        }
        return parent::prepareItem($item);
    }
}
