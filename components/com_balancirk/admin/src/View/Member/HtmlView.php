<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Member;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\Toolbar;

/**
 * View class for a list of member
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
     * The students
     *
     * @var  array
     */
    protected $sudents;

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
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->students = $this->get('Students');

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
        $isNew = ($this->item->id == 0);

        $canDo = ContentHelper::getActions('com_balancirk');

        $toolbar = Toolbar::getInstance();

        ToolbarHelper::title(
            Text::_('COM_BALANCIRK_MEMBER_PAGE_TITLE_' . ($isNew ? 'ADD_MEMBER' : 'EDIT_MEMBER'))
        );

        if ($canDo->get('core.create'))
        {
            if ($isNew)
            {
                $toolbar->apply('member.register');
            }
            else
            {
                $toolbar->apply('member.apply');
            }
        }

        $toolbar->cancel('member.cancel', 'JTOOLBAR_CLOSE');
    }
}
