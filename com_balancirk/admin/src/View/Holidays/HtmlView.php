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
		$this->items       = $this->get('Items');

		// Add calendar JS to view
		$document = Factory::getApplication()->getDocument();
		$document->addScript('/media/com_balancirk/js/jquery.simple-calendar.min.js')
			->addStyleSheet('/media/com_balancirk/js/simple-calendar.css')
			->addScriptDeclaration('
				jQuery(document).ready(function($) {
					let container = $("#holidayContainer").simpleCalendar({
      					fixedStartDay: 0, // begin weeks by sunday
      					disableEmptyDetails: true,
      					events: ' . json_encode($this->items) . ',
    				});
    				$calendar = container.data("plugin_simpleCalendar")
				});
			');
		parent::display($tpl);
	}
}
