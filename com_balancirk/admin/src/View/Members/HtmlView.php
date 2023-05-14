<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Members;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;

/**
 * View class for a list of members
 *
 * @since  0.0.1
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * List of members of Balancirk.
	 *
	 * @var    array list of members
	 *
	 * @since  0.0.1
	 */
	protected $members;

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
	 * @since   0.0.1
	 */
	public function display($tpl = null): void
	{
		$this->items       	 = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		if (!count($this->items) && $this->get('IsEmptyState'))
		{
			$this->setLayout('emptystate');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
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
	 * @since   0.0.1
	 */
	protected function addToolbar()
	{
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_BALANCIRK_MEMBERS_PAGE_TITLE'), 'Address member');

		$canDo = ContentHelper::getActions('com_balancirk');

		if ($canDo->get('core.create'))
		{
			$toolbar->addNew('member.add');
		}

		if ($canDo->get('core.admin'))
		{
			$toolbar->trash('members.delete', 'COM_BALANCIRK_BUTTON_DELETE')->listCheck(true);
		}

		// if ($canDo->get('core.create'))
		// {
		// 	$toolbar->preferences('com_balancirk');
		// }
	}
}
