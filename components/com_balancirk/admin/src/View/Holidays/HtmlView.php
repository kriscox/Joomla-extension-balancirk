<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Holidays;

\defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of holidays
 *
 * @since  1.2.9
 */
class HtmlView extends BaseHtmlView
{
    /**
     * List of holidays of Balancirk.
     *
     * @var	$items;
     * @since  1.2.9
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \JPagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \JObject
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var  \JForm
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * Method to display the view.
     *
     * @param   string $tpl A template file to load. [optional]
     *
     * @return  void
     *
     * @since   1.2.9
     */
    public function display($tpl = null): void
    {
        /** @var array $items	List of holidays of Balancirk. */
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (!count($this->items) && $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
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
     * @since   1.2.9
     */
    protected function addToolbar()
    {
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(
            Text::_('COM_BALANCIRK_HOLIDAY_TITLE')
        );

        $canDo = ContentHelper::getActions('com_balancirk');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('holiday.add');
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('holidays.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }
    }
}
