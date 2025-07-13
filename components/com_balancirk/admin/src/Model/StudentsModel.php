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
use Joomla\CMS\Helper\ContentHelper;

/**
 * StudentsModel class to display the list off students.
 *
 * @since  0.0.1
 */
class StudentsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     \JControllerLegacy
     * @see     \Joomla\CMS\MVC\Controller\BaseController
     *
     * @since   __BUMP_VERSION__
     */
    public function __construct($config = [])
    {

        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id',
                'a.id',
                'name',
                'a.name',
                'firstname',
                'a.firstname',
                'email',
                'a.email',
                'street',
                'a.street',
                'number',
                'a.number',
                'bus',
                'a.bus',
                'postcode',
                'a.postcode',
                'city',
                'a.city',
                'phone',
                'a.phone',
                'birthdate',
                'a.birthdate',
                'state',
                'a.state'
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
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.firstname',
                    'a.email',
                    'a.street',
                    'a.number',
                    'a.bus',
                    'a.postcode',
                    'a.city',
                    'a.phone',
                    'a.birthdate',
                    'a.state'
                ],
                [
                    'id',
                    'name',
                    'firstname',
                    'email',
                    'street',
                    'number',
                    'bus',
                    'postcode',
                    'city',
                    'phone',
                    'birthdate',
                    'state'
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_students', 'a'));

        // Based on the user access level, we need to filter the results.
        // What Access Permissions does this user have? What can (s)he do?
        $this->canDo = ContentHelper::getActions('com_balancirk');
        if (!$this->canDo->get('students.viewall'))
        {
            $query->join('INNER', $db->quoteName('#__balancirk_parents', 'p'), 'a.id = p.child AND p.parent = ' . Factory::getApplication()->getIdentity()->id);
        }


        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published))
        {
            $query->where($db->quoteName('a.state') . ' = :published');
            $query->bind(':published', $published, ParameterType::INTEGER);
        }
        elseif ($published === '')
        {
            $query->where('(' . $db->quoteName('a.state') . ' = 0 OR ' . $db->quoteName('a.state') . ' = 1)');
        }

        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(a.name LIKE ' . $search . ' OR a.firstname LIKE ' . $search . ')');
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get a list of walks.
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
}
