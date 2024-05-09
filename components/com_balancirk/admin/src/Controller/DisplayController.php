<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Balancirk master display controller.
 *
 * @since  0.0.1
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  0.0.1
     */
    protected $default_view =  'members';

    /**
     * Method to display a view.
     *
     * @param   boolean  cachable   If true, the view output will be cached
     * @param   array    urlparams  An array of safe URL parameters and their variable types, for valid values see {@link FilterInput::clean()}.
     *
     * @return  BaseController|bool  This object to support chaining.
     *
     * @since   0.0.1
     *
     * @throws  \Exception
     */
    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }
}
