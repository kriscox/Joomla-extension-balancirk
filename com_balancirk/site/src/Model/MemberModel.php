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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Member model for the Joomla Balancirk component.
 *
 * @since  0.0.1
 */
class MemberModel extends BaseDatabaseModel
{
    /**
     * @var    
     */
    protected $member;

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
