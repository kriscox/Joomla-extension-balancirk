<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * student table class.
 *
 * @since  0.0.1
 */
class StudentsTable extends Table
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
        $this->typeAlias = 'com_balancirk.student';
        parent::__construct('#__balancirk_students', 'id', $db);
    }

    /**
     * Ensure boolean columns keep an explicit 0/1 value before store.
     *
     * @param   boolean  $updateNulls  True to update null values as well.
     *
     * @return  boolean  True on success.
     *
     * @since   1.3.27
     */
    public function store($updateNulls = false)
    {
        $this->increased_benefit = !empty($this->increased_benefit) ? 1 : 0;
        $this->allow_photo = !empty($this->allow_photo) ? 1 : 0;

        return parent::store($updateNulls);
    }
}
