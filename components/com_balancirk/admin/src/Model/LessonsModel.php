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

use CoCoCo\Component\Balancirk\Site\Helper\LessonAgeHelper;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * LessonsModel class to display the list off lessons.
 *
 * @since  0.0.1
 */
class LessonsModel extends ListModel
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
                'name', 'a.name',
                'type', 'a.type',
                'fee', 'a.fee',
                'year', 'a.year',
                'min_age', 'a.min_age',
                'max_age', 'a.max_age',
                'start', 'a.start',
                'end', 'a.end',
                'start_registration', 'a.start_registration',
                'end_registration', 'a.end_registration',
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
                    'a.id', 'a.name', 'a.type', 'a.fee', 'a.year',
                    'a.min_age', 'a.max_age', 'a.start', 'a.end', 'a.start_registration',
                    'a.end_registration'
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_lessons_complete', 'a'));

        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(a.name LIKE ' . $search . ')');
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get a list of lessons.
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
     * Method to get list of lessons open for subscription
     *
     * Lessons open for subscription.
     *
     * @return array
     **/
    public function getOpenLessons(?int $studentId = null, ?string $date = null): array
    {
        $rows = $this->getOpenLessonItems($studentId, $date);
        $lessons = [];

        foreach ($rows as $row)
        {
            $lessons[(int) $row->id] = $row->name;
        }

        return $lessons;
    }

    /**
     * Method to get lesson records open for subscription.
     *
     * @param   int|null     $studentId  Selected student id.
     * @param   string|null  $date       Reference date (Y-m-d).
     *
     * @return  array
     *
     * @since   1.2.29
     */
    public function getOpenLessonItems(?int $studentId = null, ?string $date = null): array
    {
        // Create a new query object.
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Get the current date
        $today = $date ?: date('Y-m-d');

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.type',
                    'a.fee',
                    'a.year',
                    'a.state',
                    'a.start',
                    'a.min_age',
                    'a.max_age'
                ]
            )
        )
            ->from($db->quoteName('#__balancirk_lessons', 'a'))
            ->where($db->quote($today) . ' between `start_registration` and `end_registration`')
            ->where($db->quoteName('a.state') . ' = 1')
            ->order('name');

        if ($studentId !== null && $studentId > 0)
        {
            $subscriptionQuery = $db->getQuery(true)
                ->select('1')
                ->from($db->quoteName('#__balancirk_subscriptions', 's'))
                ->where($db->quoteName('s.lesson') . ' = ' . $db->quoteName('a.id'))
                ->where($db->quoteName('s.student') . ' = ' . (int) $studentId);

            $query->where('NOT EXISTS (' . $subscriptionQuery . ')');
        }

        $rows = $db->setQuery($query)->loadObjectList() ?: [];
        $studentBirthdate = $studentId ? $this->getStudentBirthdate($studentId) : null;
        $lessons = [];

        foreach ($rows as $row)
        {
            if ($studentBirthdate !== null && !LessonAgeHelper::matchesLesson($studentBirthdate, $row))
            {
                continue;
            }

            $lessons[] = $row;
        }

        return $lessons;
    }

    /**
     * Load birthdate for a student.
     *
     * @param   int  $studentId  Student id.
     *
     * @return  string|null
     *
     * @since   1.2.29
     */
    private function getStudentBirthdate(int $studentId): ?string
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('birthdate'))
            ->from($db->quoteName('#__balancirk_students'))
            ->where($db->quoteName('id') . ' = ' . (int) $studentId);
        $db->setQuery($query);
        $value = $db->loadResult();

        return $value !== null ? (string) $value : null;
    }
}
