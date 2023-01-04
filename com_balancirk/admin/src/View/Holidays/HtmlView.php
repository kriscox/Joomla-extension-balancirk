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

		// Add calendar JS to view
		$document = Factory::getApplication()->getDocument()
			->addScript('/media/com_balancirk/js/jquery.simple-calendar.min.js')
			->addStyleSheet('/media/com_balancirk/js/simple-calendar.css')
			->addScriptDeclaration('
				jQuery(document).ready(function() {
					let container = $("main").find("#holidayContainer").simpleCalendar({
      					fixedStartDay: 0, // begin weeks by sunday
      					disableEmptyDetails: true,
      					events: ' . json_encode($this->items) . ',
						insertCallback : () => {window.holidays = this.events},
    				});
    				$calendar = container.data("plugin_simpleCalendar")
				});
			');

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
		$toolbar = Toolbar::getInstance();

		ToolbarHelper::title(
			Text::_('COM_BALANCIRK_HOLIDAY_TITLE')
		);

		$toolbar->apply('holidays.save');

		$toolbar->cancel('holidays.cancel', 'JTOOLBAR_CLOSE');
	}
}
