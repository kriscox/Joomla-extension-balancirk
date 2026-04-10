<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\View\Lessons;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text as Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;

/**
 * HTML Lessons view class for the balancirk component.
 *
 * @since  1.2.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * List of students of Balancirk.
     *
     * @var  array
     * @since  0.0.1
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \JPagination
     */
    protected $pagination;

    /**
     * Form object for search filters
     *
     * @var  \JForm
     */
    public $filterForm;

    /**
     * The model state
     *
     * @var  \JObject
     */
    protected $state;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * The actions the user is authorised to perform
     *
     * @var  \JObject
     */
    protected $canDo;

    /**
     * The years for filtering
     *
     * @var  array
     */
    protected $years;

    /**
     * Whether the current user can export accounting data.
     *
     * @var  bool
     */
    public $canExportAccounting = false;

    /**
     *  Execute and display template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @since   0.0.1
     */
    public function display($tpl = null)
    {
        // What Access Permissions does this user have? What can (s)he do?
        $this->canDo = ContentHelper::getActions('com_balancirk');

        // Only teachers with lessons.view permission can see lessons
        if (!$this->canDo->get('lessons.view'))
        {
            throw new Exception(Text::_('COM_BALANCIRK_ERROR_TEACHERS_ONLY'));
        }

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check if user can export accounting data
        $this->canExportAccounting = Factory::getApplication()->getIdentity()->authorise('accounting.export', 'com_balancirk');

        // Get list of years for filtering
        /** @var LessonsModel */
        $lessonsModel = $this->getModel();
        $this->years = $lessonsModel->getYears();

        if (!$this->items || (!count($this->items) && $this->get('IsEmptyState')))
        {
            $this->setLayout('emptystate');
        }

        return parent::display($tpl);
    }
}
