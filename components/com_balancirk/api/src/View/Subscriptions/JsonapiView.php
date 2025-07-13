<?php

namespace CoCoCo\Component\Balancirk\Api\View\Subscriptions;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Tobscure\JsonApi\Resource;
use Joomla\CMS\MVC\View\Event\OnGetApiFields;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Router\Exception\RouteNotFoundException;

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
        'studentid',
        'name',
        'firstname',
        'lessonid',
        'lesson',
        'type',
        'fee',
        'year',
        'start',
        'end',
        'start_registration',
        'end_registration',
        'state',
        'subscribed'
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
        'lesson',
        'year'
    ];

    /**
     * Execute and display a template script.
     *
     * @param   object  $item  Item
     *
     * @return  string
     *
     * @since   4.0.0
     */
    public function displayItem($item = null)
    {
        if ($item === null)
        {
            /** @var \CoCoCo\Component\Balancirk\Administrator\SubscriptionModel; $model */
            $model = $this->getModel();
            $item  = $this->prepareItem($model->getItemFull());
        }

        if ($item->id === null)
        {
            throw new RouteNotFoundException('Item does not exist');
        }

        // Check for errors.
        if (\count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if ($this->type === null)
        {
            throw new \RuntimeException('Content type missing');
        }

        $eventData = [
            'type'      => OnGetApiFields::ITEM,
            'fields'    => $this->fieldsToRenderItem,
            'relations' => $this->relationship,
            'context'   => $this->type,
        ];
        $event     = new OnGetApiFields('onApiGetFields', $eventData);

        /** @var OnGetApiFields $eventResult */
        $eventResult = Factory::getApplication()->getDispatcher()->dispatch('onApiGetFields', $event);

        $element = (new Resource($item, $this->serializer))
            ->fields([$this->type => $eventResult->getAllPropertiesToRender()]);

        if (!empty($this->relationship))
        {
            $element->with($eventResult->getAllRelationsToRender());
        }

        $this->getDocument()->setData($element);
        $this->getDocument()->addLink('self', Uri::current());

        return $this->getDocument()->render();
    }
}
