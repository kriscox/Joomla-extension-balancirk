<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo, Inc. All rights reserved.
 * @license     GNU General Public License version 3 see LICENSE.txt
 */

namespace CoCoCo\Component\Balancirk\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of member records.
 *
 * @since  __BUMP_VERSION__
 */
class StudentsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  a member list.
     *
     * @see     \JControllerLegacy
     *
     * @since   __BUMP_VERSION__
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }
    /**
     * Build an SQL query to load the list data.
     *
     * @return  \JDatabaseQuery
     *
     * @since   __BUMP_VERSION__
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $db->quoteName([
                'id', 'name', 'surname', 'username',
                'email', 'street', 'number', 'bus', 'postalcode',
                'municipality', 'phone', 'userid_joomla'
            ])
        );
        $query->from($db->quoteName('#__members'));

        return $query;
    }
}
