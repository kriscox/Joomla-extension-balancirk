<?php

namespace CoCoCo\Component\Balancirk\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

/**
 * Balancirk Component Controller
 * @since  0.0.1
 */
class DisplayController extends BaseController
{

    public function display($cachable = false, $urlparams = array())
    {
        $document = Factory::getDocument();
        $viewName = $this->input->getCmd('view', 'login');
        $viewFormat = $document->getType();

        $view = $this->getView($viewName, $viewFormat);
        $view->setModel($this->getModel('Student'), true);

        $view->document = $document;
        $view->display();
    }
}
