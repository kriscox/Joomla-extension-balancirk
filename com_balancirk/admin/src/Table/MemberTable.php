<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo, Inc. 
 * @license     GNU General Public License version 3
 */

namespace CoCoCo\Component\Balancirk\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;

/**
 * Members Table class.
 *
 * @since  __BUMP_VERSION__
 */
class MemberTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   __BUMP_VERSION__
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_balancirk.member';
        parent::__construct('#__members', 'id', $db);
    }
    /**
     * Generate a valid alias from title / date.
     * Remains public to be able to check for duplicated alias before saving
     *
     * @return  string
     */
    public function generateAlias()
    {
        if (empty($this->alias)) {
            $this->alias = $this->name;
        }
        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);
        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }
        return $this->alias;
    }
}
