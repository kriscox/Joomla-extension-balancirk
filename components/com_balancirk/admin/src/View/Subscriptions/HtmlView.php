<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Subscriptions;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;

/**
 * View class for a list of subscriptions.
 *
 * @since  1.3.21
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items.
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object.
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The model state.
     *
     * @var  \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * Form object for search filters.
     *
     * @var  \Joomla\CMS\Form\Form
     */
    public $filterForm;

    /**
     * The active search filters.
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * Whether the user may create subscriptions.
     *
     * @var  bool
     */
    public $canCreate = false;

    /**
     * Whether the user may delete subscriptions.
     *
     * @var  bool
     */
    public $canDelete = false;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   1.3.21
     */
    public function display($tpl = null): void
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $actions = ContentHelper::getActions('com_balancirk');
        $this->canCreate = $actions->get('subscriptions.create')
            || $actions->get('lessons.admin')
            || $actions->get('core.admin');
        $this->canDelete = $actions->get('subscriptions.delete')
            || $actions->get('students.viewall')
            || $actions->get('lessons.admin')
            || $actions->get('core.delete')
            || $actions->get('core.admin');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.3.21
     */
    protected function addToolbar()
    {
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_BALANCIRK_SUBSCRIPTIONS_PAGE_TITLE'), 'users subscriptions');

        if ($this->canCreate) {
            $toolbar->addNew('subscription.add');
        }

        if ($this->canDelete) {
            $toolbar->delete('subscriptions.delete')
                ->text('JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($this->canCreate) {
            $toolbar->preferences('com_balancirk');
        }
    }
}
