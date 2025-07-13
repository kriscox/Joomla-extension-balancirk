<?php

namespace CoCoCo\Component\Balancirk\Api\View\Students;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class JsonapiView extends BaseApiView
{
    /**
     * The fields to render item in the documents
     *
     * @var     array
     * @since  __BUMP_VERSION__
     */
    protected $fieldsToRenderItem = [
        'id',
        'name',
        'firstname',
        'email',
        'street',
        'number',
        'bus',
        'postcode',
        'city',
        'phone',
        'birthdate',
    ];

    /**
     * The fields to renders item in the documents
     *
     * @var  array
     * @since  4.0.0
     */
    protected $fieldsToRenderList = [
        'id',
        'name',
        'firstname',
        'email',
        'phone'
    ];
}
