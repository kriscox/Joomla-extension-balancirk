<?php

namespace CoCoCo\Component\Balancirk\Api\View\Presences;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class JsonapiView extends BaseApiView
{
    protected $fieldsToRenderItem = [
        'student',
    ];

    protected $fieldsToRenderList = [
        'student',
    ];
}
