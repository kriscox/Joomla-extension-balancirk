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
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // SPA layouts delegate all data loading to the Angular app via REST API.
        if ($tpl !== 'spa' && $tpl !== 'spaadmin') {
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
        }

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        return parent::display($tpl);
    }
}
