<?php

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Controller;

use Joomla\CMS\MVC\Controller\FormController;

\defined('_JEXEC') or die;

/**
 * Balancirk student controller.
 *
 * @since   0.0.1
 */
class StudentController extends FormController
{
    /**
     * Cancel and return to the students list page.
     *
     * Implement the cancel button to return to the students list page on pressing the button with
     * task student.cancel
     *
     * @param   array	   $key	List of fields of the for
     *
     * @since   __BUMP_VERSION__
     **/
    public function cancel($key = null)
    {
        parent::cancel($key);

        // Set up the redirect back to the previous page (put in the header in HtmlView.php)
        $this->redirect(
            '/administrator/index.php?option=' . $this->option . '&view=students'
        );
    }
}
