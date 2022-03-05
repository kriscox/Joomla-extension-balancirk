<?php

/**
 * @package 	Joomla.Administrator
 * @subpackage 	com_Balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

namespace CoCoCo\Component\Balancirk\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default Controller for Com_balancirk
 *
 * @package     Joomla.Administrator
 * @subpackage  com_Balancirk
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method
     * 
     * @var string
     */
    protected $default_view = 'overview';

    public function display($cachable = false, $urlparams = array())
    {
        return parent::display($cachable, $urlparams);
    }
}
