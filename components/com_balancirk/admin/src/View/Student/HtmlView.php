<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Student;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * View class for a list of student.
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
     * The parents
     *
     * @var  array
     */
    protected $parents;

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
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');
        $this->parents = $this->get('Parents');

        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        return parent::display($tpl);
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
        $isNew      = ($this->item->id == null);

        $canDo = ContentHelper::getActions('com_balancirk');

        $toolbar = Toolbar::getInstance();

        ToolbarHelper::title(
            Text::_('COM_BALANCIRK_STUDENT_PAGE_TITLE_' . ($isNew ? 'ADD_STUDENT' : 'EDIT_STUDENT'))
        );

        if ($canDo->get('core.create'))
        {
            if ($isNew)
            {
                $toolbar->apply('student.save');
            }
            else
            {
                $toolbar->apply('student.apply');
            }
        }

        $toolbar->cancel('student.cancel', 'JTOOLBAR_CLOSE');
    }
}
