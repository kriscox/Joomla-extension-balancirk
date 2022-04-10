<?php

/**
 * @package 	joomla.Administrator
 * @subpackage 	com_Balancirk
 *
 * @copyright	Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	    GNU General Public License version 3; see LICENSE.txt
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Students;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Main "Students" Admin View
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Display the main "Students" view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null): void
    {
        $this->items = $this->get('Items');

        if (!count($this->items) && $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }
        $this->addToolbar();


        parent::display($tpl);
    }

    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        ToolbarHelper::title(Text::_('COM_BALANCIRK_MANAGER_STUDENTS'), 'address student');
        $toolbar->addNew('student.add');
    }
}
