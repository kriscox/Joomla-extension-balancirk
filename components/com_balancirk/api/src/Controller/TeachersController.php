<?php

/**
 * @package     Joomla.API
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Api\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Exception;

/**
 * undocumented class
 */
class TeachersController extends ApiController
{
    protected $contentType = 'teachers'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'teachers'; /* This maps to the folder name containing the JSON API view */

    public function getteacher()
    {
        $lesson = $this->input->get('lesson');
        $date = $this->input->get('date');

        /** @var \CoCoCo\Component\Balancirk\Administrator\Model\TeacherModel $model **/
        $model = $this->getModel('Teachers');
        $model->getPresences($lesson, $date);

        return $this->displayListModel($model);
    }

    /**
     * Basic display of a list view
     *
     * @return  static  A \JControllerLegacy object to support chaining.
     *
     * @since   4.0.0
     */
    public function displayListModel($model)
    {
        // Assemble pagination information (using recommended JsonApi pagination notation for offset strategy)
        $paginationInfo = $this->input->get('page', [], 'array');
        $limit          = null;
        $offset         = null;

        if (\array_key_exists('offset', $paginationInfo))
        {
            $offset = $paginationInfo['offset'];
            $this->modelState->set($this->context . '.limitstart', $offset);
        }

        if (\array_key_exists('limit', $paginationInfo))
        {
            $limit = $paginationInfo['limit'];
            $this->modelState->set($this->context . '.list.limit', $limit);
        }

        $viewType   = $this->app->getDocument()->getType();
        $viewName   = $this->input->get('view', $this->default_view);
        $viewLayout = $this->input->get('layout', 'default', 'string');

        try
        {
            /** @var JsonApiView $view */
            $view = $this->getView(
                $viewName,
                $viewType,
                '',
                ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]
            );
        }
        catch (\Exception $e)
        {
            throw new \RuntimeException($e->getMessage());
        }

        if (!$model)
        {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        // Push the model into the view (as default)
        $view->setModel($model, true);

        if ($offset)
        {
            $model->setState('list.start', $offset);
        }

        /**
         * Sanity check we don't have too much data being requested as regularly in html we automatically set it back to
         * the last page of data. If there isn't a limit start then set
         */
        if ($limit)
        {
            $model->setState('list.limit', $limit);
        }
        else
        {
            $model->setState('list.limit', $this->itemsPerPage);
        }

        if (!\is_null($offset) && $offset > $model->getTotal())
        {
            throw new Exception\ResourceNotFound();
        }

        $view->document = $this->app->getDocument();

        $view->displayList();

        return $this;
    }
}
