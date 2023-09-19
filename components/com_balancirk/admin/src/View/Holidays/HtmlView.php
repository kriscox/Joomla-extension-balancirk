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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Application\CMSWebApplicationInterface;

/**
 * View class for a list of holidays
 *
 * @since  0.0.1
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * List of holidays of Balancirk.
	 *
	 * @var	$holidays;
	 * @since  0.0.1
	 */
	protected $items;

	/**
	 * Form object for the view.
	 *
	 * @var		$form;
	 * @since	0.0.1
	 */
	protected $form;

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
		$this->form = $this->get('form');
		$this->items = $this->get('Items');
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
		Factory::getApplication()->input->set('hidemainmenu', true);
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(
			Text::_('COM_BALANCIRK_HOLIDAY_TITLE')
		);

		$toolbar->addNew('holidays.new');
		$toolbar->apply('holidays.save');
		$toolbar->cancel('holidays.cancel', 'JTOOLBAR_CLOSE');
	}
}
