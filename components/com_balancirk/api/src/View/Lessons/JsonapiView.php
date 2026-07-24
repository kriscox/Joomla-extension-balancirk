<?php

/**
 * @package     Joomla.API
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Api\View\Lessons;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

/**
 * JSON:API view for lessons list/item.
 *
 * @since  1.4.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * @var  array
     */
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
        'state',
        'lesdays',
        'max_students',
        'min_age',
        'max_age',
        'numberOfStudents',
        'numberOnWaitingList',
    ];

    /**
     * @var  array
     */
    protected $fieldsToRenderList = [
        'id',
        'name',
        'type',
        'fee',
        'year',
        'start',
        'end',
        'start_registration',
        'end_registration',
        'state',
        'lesdays',
        'max_students',
        'min_age',
        'max_age',
        'numberOfStudents',
        'numberOnWaitingList',
    ];

    /**
     * @param   array|null  $items  Items
     *
     * @return  string
     */
    public function displayList(?array $items = null)
    {
        foreach (FieldsHelper::getFields('com_balancirk.lesson') as $field) {
            $this->fieldsToRenderList[] = $field->id;
        }

        return parent::displayList();
    }

    /**
     * @param   object|null  $item  Item
     *
     * @return  string
     */
    public function displayItem($item = null)
    {
        foreach (FieldsHelper::getFields('com_balancirk.lesson') as $field) {
            $this->fieldsToRenderItem[] = $field->name;
        }

        return parent::displayItem($item);
    }

    /**
     * @param   object  $item  Item
     *
     * @return  object
     */
    protected function prepareItem($item)
    {
        foreach (FieldsHelper::getFields('com_balancirk.lesson', $item, true) as $field) {
            $item->{$field->name} = isset($field->apivalue) ? $field->apivalue : $field->rawvalue;
        }

        return parent::prepareItem($item);
    }
}
