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

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML Member view class for the balancirk component.
 *
 * @since  0.0.1
 */
class HtmlView extends BaseHtmlView
{
    /**
     *  Execute and display template script.
     *
     * @param  string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @since   0.0.1
     */
    public function display($tpl = null)
    {
        $this->member = $this->get('Member');

        return parent::display($tpl);
    }
}
