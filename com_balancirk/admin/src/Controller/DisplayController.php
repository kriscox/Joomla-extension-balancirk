<?php

/**
 * @package 	Joomla.Administrator
 * @subpackage 	com_Balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

namespace CoCoCo\Component\Balancirk\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default Controller for Com_balancirk
 *
 * @package     Joomla.Administrator
 * @subpackage  com_Balancirk
 * @since       __DEPLOY_VERSION__
 */
class DisplayController extends BaseController
{

    /**
     * The default view.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $default_view = 'overview';

    public function display($cachable = false, $urlparams = array())
    {
        return parent::display();
    }
}
