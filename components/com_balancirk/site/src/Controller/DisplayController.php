<?php
    namespace CoCoCo\Component\Balancirk\Site\Controller;
    defined('_JEXEC') or die;

    use Joomla\CMS\MVC\Controller\BaseController;
    use Joomla\CMS\Factory;

    /**
    * @package     Joomla.Site
    * @subpackage  com_balancirk
    *
    * @copyright   CoCoCo
    * @license     Copyright (C)  2022 GPL v2 All rights reserved.
    */

    /**
    * Balancirk Component Controller
    * @since  1.2.5
    */
    class DisplayController extends BaseController {

        public function display($cachable = false, $urlparams = array()) {
            $document = Factory::getDocument();
            $viewName = $this->input->getCmd('view', 'login');
            $viewFormat = $document->getType();

            $view = $this->getView($viewName, $viewFormat);
            $view->setModel($this->getModel('Message'), true);

            $view->document = $document;
            $view->display();
        }

    }
    