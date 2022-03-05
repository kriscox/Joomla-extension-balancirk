<?php

namespace CoCoCo\Component\Balancirk\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;


/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

/**
 * Balancirk Student ListModel
 * @since 0.0.1
 */
class BalancirkStudentListModel extends ListModel
{

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     \JController
     * @since   0.0.1
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'firstname', 'a.firstname',
                'name', 'a.name',
                'parent_id', 'a.parent_id',
                'birthdate', 'a.birthdate',
                'dialcode', 'a.dialcode',
                'phone', 'a.phone',
                'email', 'a.email',
                'remarks', 'a.remarks',
                'use_photos', 'a.use_photos',
                'uitpassnr', 'a.uitpassnr'
            );
        }

        parent::__construct($config);
    }


    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  JTable  A JTable object
     *
     * @since   0.0.1
     */
    public function getTable($type = 'StudentTable', $prefix = 'Balancirk', $config = null)
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \JDatabaseQuery
     *
     * @since   0.0.1
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                array(
                    'a.id', 'a.firstname', 'a.name', 'a.parent_id', 'a.birthdate',
                    'a.dialcode', 'a.phone', 'a.email', 'a.remarks',
                    'a.use_photos', 'a.uitpassnr'
                )
            )
        );

        $query->from($db->quoteName($this->getTable(), 'a'));

        // Join with users (parent is joomla-user)
        $query->select($db->quoteName('p.name'))
            ->join(
                'INNER',
                $db->quoteName('#__users', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.parent_id')
            );

        // Filter on the current logged-in user as parent
        if ($parent = User::getIdentity()) {
            $query->where($db->quoteName('a.parent_id') . ' = ' . $db->quote($parent));
        }

        return $query;
    }

    /**
     * Get student information from table to be shown
     * 
     *  @return 
     * 
     *  @since 0.0.1
     */
    public function getStudentInformation($id)
    {
        $children = getItems();
    }
}
