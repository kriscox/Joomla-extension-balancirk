<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\View\Member;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

/**
 * HTML Member view class for the balancirk component.
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

    protected $students;

    protected $subscriptions;

    protected $years;

    protected $selectedYear;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        // Legacy menu items still point at member&layout=spa|spaadmin.
        if ($tpl === 'spa' || $tpl === 'spaadmin') {
            Factory::getApplication()->redirect(
                Route::_('index.php?option=com_balancirk&view=spa', false)
            );

            return;
        }

        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        /** @var MVCFactoryInterface $factory */
        $factory = Factory::getApplication()
            ->bootComponent('com_balancirk')
            ->getMVCFactory();

        $studentsModel     = $factory->createModel('Students', 'Site');
        $subscriptionsModel = $factory->createModel('Subscriptions', 'Site');

        $app = Factory::getApplication();
        $selectedYear = $app->input->getString('filter_year', '');

        if ($selectedYear !== '') {
            $subscriptionsModel->setState('filter.year', $selectedYear);
        }

        $this->students      = $studentsModel->getItems();
        $this->subscriptions = $subscriptionsModel->getItems();
        $this->years         = $subscriptionsModel->getYears();
        $this->selectedYear  = $subscriptionsModel->getState('filter.year');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        return parent::display($tpl);
    }
}
