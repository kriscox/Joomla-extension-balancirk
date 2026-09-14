<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\View\Subscription;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * View class for enrolling a student.
 *
 * @since  1.3.21
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The form object.
     *
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * The active item.
     *
     * @var  object
     */
    protected $item;

    /**
     * The model state.
     *
     * @var  object
     */
    protected $state;

    /**
     * Base64 return URL after save/cancel.
     *
     * @var  string
     */
    public $return = '';

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  mixed
     *
     * @since   1.3.21
     */
    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');
        $this->return = (string) Factory::getApplication()->getInput()->get('return', '', 'RAW');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $actions = ContentHelper::getActions('com_balancirk');
        $canCreate = $actions->get('subscriptions.create')
            || $actions->get('lessons.admin')
            || $actions->get('core.admin');

        if (!$canCreate) {
            throw new GenericDataException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->addToolbar($canCreate);

        return parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @param   bool  $canCreate  Whether the user may enrol students.
     *
     * @return  void
     *
     * @since   1.3.21
     */
    protected function addToolbar(bool $canCreate = false)
    {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $toolbar = Toolbar::getInstance();

        ToolbarHelper::title(Text::_('COM_BALANCIRK_SUBSCRIPTION_PAGE_TITLE_ADD'), 'user-plus');

        if ($canCreate) {
            $toolbar->save('subscription.save', 'COM_BALANCIRK_SUBSCRIPTION_TOOLBAR_ENROL');
        }

        $toolbar->cancel('subscription.cancel', 'JTOOLBAR_CLOSE');
    }
}
