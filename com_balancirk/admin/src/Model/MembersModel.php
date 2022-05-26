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
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of member records.
 *
 * @since  __BUMP_VERSION__
 */
class MembersModel extends ListModel
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
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'name', 'a.name',
                'surname', 'a.surname',
                'email', 'a.email',
                'street', 'a.street',
                'postalcode', 'a.postalcode',
                'municipality', 'a.municipality',
                'ordering', 'a.ordering',
            );

            $assoc = Associations::isEnabled();
            if ($assoc) {
                $config['filter_fields'][] = 'association';
            }
        }

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
            $db->quoteName(
                array(
                    'id', 'name', 'surname', 'username',
                    'email', 'street', 'number', 'bus', 'postalcode',
                    'municipality', 'phone', 'userid_joomla'
                )
            )
        );
        $query->from($db->quoteName('#__members'), 'a');

        // Filter by Email 
        $email = $this->getState('filter.email');
        if (!empty($email)) {
            $email = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($email), true) . '%'));
            $query->where($db->quoteName('a.email') . ' LIKE ' . $email . ')');
        }

        // Filter by search in name, surname, street, municipality
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where(
                    '((' . $db->quoteName('a.name') . ' LIKE ' . $search . ')' . 'OR' .
                        '(' . $db->quoteName('a.surname') . ' LIKE ' . $search . ')' . 'OR' .
                        '(' . $db->quoteName('a.street') . ' LIKE ' . $search . ')' . 'OR' .
                        '(' . $db->quoteName('a.municipality') . ' LIKE ' . $search . '))'
                );
            }
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
