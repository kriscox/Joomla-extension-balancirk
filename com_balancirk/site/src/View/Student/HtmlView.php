<?php

/**
 * @package 	joomla.Site
 * @subpackage 	com_Balancirk
 *
 * @copyright	Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	    GNU General Public License version 3; see LICENSE.txt
 */

namespace CoCoCo\Component\Balancirk\Site\View\Student;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Main "Student" Admin View
 * 
 * @since  __BUMP_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The student object details
     *
     * @var    \JObject
     * @since  __BUMP_VERSION__
     */
    protected $student;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    function display($tpl = null)
    {
        $this->student = $this->get('Student');
        parent::display($tpl);
    }
}
