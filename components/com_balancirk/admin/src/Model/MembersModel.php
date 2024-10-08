<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * MembersModel class to display the list off members.
 *
 * @since  0.0.1
 */
class MembersModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     \JControllerLegacy
     *
     * @since   __BUMP_VERSION__
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'username', 'a.username',
                'name', 'a.name',
                'firstname', 'a.firstname',
                'email', 'a.email',
                'street', 'a.street',
                'number', 'a.number',
                'bus', 'a.bus',
                'postcode', 'a.postcode',
                'city', 'a.city',
                'phone', 'a.phone',
                'activation', 'a.activation'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   0.0.1
     */
    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   0.0.1
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
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
                [
                    'a.id', 'a.name', 'a.firstname', 'a.username',
                    'a.email', 'a.street', 'a.number', 'a.bus', 'a.postcode',
                    'a.city', 'a.phone', 'a.block', 'a.sendEmail',
                    'a.registerDate', 'a.lastvisitDate', 'a.activation'
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_members', 'a'));

        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(a.name LIKE ' . $search . ')', 'OR');
            $query->where('(a.firstname LIKE ' . $search . ')');
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get a list of members.
     * Overridden to add a check for access levels.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   __BUMP_VERSION__
     */
    public function getItems()
    {
        $items = parent::getItems();

        return $items;
    }


    /**
     * Delete the member
     *
     * Delete the member with the given id from the joomla tables
     * and also from the internal tables
     *
     * @param   array $pks	An array of record primary keys.
     * @return  boolean    	True if successful, false if an error occurs.
     * @throws  conditon
     **/
    public function delete(&$pks)
    {
        $return = true;

        foreach ($pks as $id) {
            // Check if value is numeric, otherwise skip value
            if (!is_numeric($id)) {
                break;
            }

            // remove addition information from user
            $dbo = $this->getDbo();
            $query = $dbo->getQuery(true)->delete($dbo->quoteName('#__balancirk_members_additional'));

            $conditions = array(
                $dbo->quoteName('id') . " = " . $id
            );

            $query->where($conditions);

            $dbo->setQuery($query);

            if (!$dbo->execute()) {
                $return = false;
                break;
            }

            // Remove system user
            $user = Factory::getApplication()->getIdentity($id);

            if (!$user->delete()) {
                $return = false;
            }
        }

        return $return;
    }
}
