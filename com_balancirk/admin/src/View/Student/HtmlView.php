<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo, Inc. All rights reserved.
 * @license     GNU General Public License version 3
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Student;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit a student.
 *
 * @since  __BUMP_VERSION__
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
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item = $this->get('Item');
        $this->addToolbar();
        return parent::display($tpl);
    }
    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   __BUMP_VERSION__
     */
    protected function addToolbar()
    {
        Factory::getApplication()->input->set('hidemainmenu', true);
        $isNew = ($this->item->id == 0);
        ToolbarHelper::title($isNew ? Text::_('COM_BALANCIRK_MANAGER_STUDENT_NEW') : Text::_('COM_BALANCIRK_MANAGER_STUDENT_EDIT'), 'address student§');
        ToolbarHelper::apply('student.apply');
        ToolbarHelper::cancel('student.cancel', 'JTOOLBAR_CLOSE');
    }
}
