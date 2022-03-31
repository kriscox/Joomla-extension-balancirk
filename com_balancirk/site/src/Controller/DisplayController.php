<?php

/**
 * @package 	Joomla.Site
 * @subpackage 	com_Balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

namespace CoCoCo\Component\Balancirk\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

/**
 * Default Controller for Com_balancirk
 *
 * @package     Joomla.Site
 * @subpackage  com_Balancirk
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method
     * 
     * @var string
     */
    protected $default_view = 'student';

    public function display($cachable = false, $urlparams = array())
    {
        return parent::display($cachable, $urlparams);
    }
}
