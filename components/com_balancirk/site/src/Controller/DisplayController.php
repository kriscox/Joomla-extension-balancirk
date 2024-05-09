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
class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = array())
    {
        parent::display($cachable, $urlparams);

        return $this;
    }
}
