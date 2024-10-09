<?php

/**
 * @package     Joomla.site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * SubscriptionsModel class to display the list off subscriptions.
 *
 * @since  0.0.1
 */
class SubscriptionsModel extends ListModel
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
                'name',
                'a.name',
                'firstname',
                'a.firstname',
                'lesson',
                'a.lesson',
                'type',
                'a.type',
                'fee',
                'a.fee',
                'year',
                'a.year',
                'start',
                'a.start',
                'end',
                'a.end',
                'start',
                'a.start_registration',
                'end_registration',
                'a.end_registration',
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
    protected function populateState($ordering = 'a.year', $direction = 'desc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $current = $this->getUserStateFromRequest($this->context . '.filter.current', 'filter_current', '');
        $this->setState('filter.current', $current);

        // List state information.
        parent::populateState($ordering, $direction);
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

        // Get the current logged in user.
        $app = \Joomla\CMS\Factory::getApplication();
        $user = $app->getIdentity();

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.firstname',
                    'a.lesson',
                    'a.type',
                    'a.fee',
                    'a.year',
                    'a.start',
                    'a.end',
                    'a.start_registration',
                    'a.end_registration',
                    'a.state',
                    'a.subscribed'
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_subscriptions_view', 'a'));

        // Filter users based on logged in user
        $query->join(
            'INNER',
            $db->quoteName('#__balancirk_parents', 'p'),
            'a.studentid = p.child AND p.parent = ' . $user->id
        );

        // Filter by current state
        $current = (string) $this->getState('filter.current');

        if (is_numeric($current))
        {
            $query->where($db->quoteName('a.state') . ' = :current');
            $query->bind(':current', $current, ParameterType::INTEGER);
        }
        elseif ($current === '')
        {
            $query->where('(' . $db->quoteName('a.state') . ' = 0 OR ' . $db->quoteName('a.state') . ' = 1)');
        }

        // Filter by selected year
        $selectedYear = $this->getState('filter.year');
        $today = date('Y-m-d');
        if (empty($selectedYear))
        {
            $query->where($db->quote(date(
                'Y',
                strtotime($today . '- 5 months')
            )) . ' = `year`');
            $this->setState('filter.year', date('Y', strtotime($today . '- 5 months')));
        }
        else
        {
            $query->where($db->quoteName('a.year') . ' = ' . $db->quote($selectedYear));
        }

        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(a.name LIKE ' . $search . ')', 'OR');
            $query->where('(a.firstname LIKE ' . $search . ')', 'OR');
            $query->where('(a.lesson LIKE ' . $search . ')');
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.year');
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

    /**
     * Get the years for filtering.
     *
     * @return  array  The years for filtering.
     *
     * @since   0.0.1
     */
    public function getYears()
    {
        // Get the current logged in user.
        $app = \Joomla\CMS\Factory::getApplication();
        $user = $app->getIdentity();

        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('DISTINCT ' . $db->quoteName('year'))
            ->from($db->quoteName('#__balancirk_subscriptions_view', 'a'))

            // Filter users based on logged in user
            ->join(
                'INNER',
                $db->quoteName('#__balancirk_parents', 'p'),
                'a.studentid = p.child AND p.parent = ' . $user->id
            )

            ->order($db->quoteName('year') . ' DESC');

        $db->setQuery($query);
        return $db->loadColumn();
    }
}
