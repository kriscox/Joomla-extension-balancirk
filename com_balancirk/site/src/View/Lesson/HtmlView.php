<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\View\Lesson;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text as Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML lesson view class for the balancirk component.
 *
 * @since  0.0.1
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The \JForm object
	 *
	 * @var  \JForm
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The students list
	 *
	 * @var  array	list of students
	 */
	protected $students;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The actions the user is authorised to perform
	 *
	 * @var  \JObject
	 */
	protected $canDo;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo = ContentHelper::getActions('com_balancirk');

		if (!$this->canDo->get('lessons.view'))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->students = $this->get('Students');
		$this->state = $this->get('State');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		return parent::display($tpl);
	}
}
