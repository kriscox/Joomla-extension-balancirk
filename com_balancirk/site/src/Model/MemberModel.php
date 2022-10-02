<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Member model for the Joomla Balancirk component.
 *
 * @since  0.0.1
 */
// TODO: Add AdminModel functionalities
// update, read, write + from 
class MemberModel extends AdminModel
{
    /**
     * @var    
     */
    protected $member;

    /**
     * getForm function.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form
     *
     * @since   0.0.1
     */
    public function getForm($data = array(), $loadData = true)
    {
    }

    /**
     * Get the member.
     *
     * @return  Member  
     */
    public function getMember()
    {
        $app = Factory::getApplication();
        $this->member = $app->input->get('which_member', "Member 1");

        return $this->member;
    }
}
