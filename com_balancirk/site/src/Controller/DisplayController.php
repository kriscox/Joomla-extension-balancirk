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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * Default Controller for Com_balancirk
 *
 * @package     Joomla.Site
 * @subpackage  com_Balancirk
 * @since       __BUMP_VERSION__    
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method
     * 
     * @var string
     */
    protected $default_view = 'student';

    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * Recognized key values include 'name', 'default_task', 'model_path', and
     * 'view_path' (this list is not meant to be comprehensive).
     * @param   MVCFactoryInterface  $factory  The factory.
     * @param   CMSApplication       $app      The JApplication for the dispatcher
     * @param   \JInput              $input    Input
     *
     * @since   __BUMP_VERSION__
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
    }

    public function display($cachable = false, $urlparams = array())
    {
        parent::display($cachable, $urlparams);

        return $this;
    }
}
