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

use DateInterval;
use Exception;
use CoCoCo\Component\Balancirk\Site\Helper\LessonAgeHelper;
use CoCoCo\Component\Balancirk\Site\Helper\SchoolYearHelper;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

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
                'type',
                'a.type',
                'year',
                'a.year',
                'numberOfStudents',
                'a.numberOfStudents',
                'state',
                'a.state'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string $ordering  The name of the ordering field.
     * @param   string $direction The direction of ordering (asc|desc).
     * @return  void
     * @throws  Exception If the state is not an object.
     **/
    protected function populateState($ordering = null, $direction = null)
    {
        $app = Factory::getApplication();

        // Load the filter state.
        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');

        $this->setState('filter.search', $search);

        // List state information.
        parent::populateState('a.name', 'asc');

        // Set limit
        $this->setState('list.limit', $_REQUEST['limit'] ?? 0);

        // Set start (eg. what record to begin pagination at)
        $this->setState('list.start', $_REQUEST['limitstart'] ?? 0);
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

        // Get the current date
        $today = date("Y-m-d");

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.type',
                    'a.year',
                    'a.state',
                    'a.numberOfStudents',
                    'a.numberOnWaitingList',
                    'a.max_students'
                ],
                [
                    'id',
                    'name',
                    'type',
                    'year',
                    'state',
                    'numberOfStudents',
                    'numberOnWaitingList',
                    'max_students'
                ]
            )
        )
            ->from($db->quoteName('#__balancirk_lessons_complete', 'a'));

        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(a.name LIKE ' . $search . ')');
        }

        // Filter by selected year
        $selectedYear = $this->getState('filter.year');
        if (empty($selectedYear))
        {
            $schoolYear = SchoolYearHelper::getCurrentSchoolYear($today);
            $query->where($db->quote($schoolYear) . ' = `year`');
            $this->setState('filter.year', $schoolYear);
        }
        else
        {
            $query->where($db->quoteName('a.year') . ' = ' . $db->quote($selectedYear));
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.name');
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
     * Method to get list of lessons open for subscription
     *
     * Lessons open for subscription.
     *
     * @return array
     **/
    public function getOpenLessons(?int $studentId = null)
    {
        $rows = $this->getOpenLessonItems($studentId);
        $lessons = [];

        foreach ($rows as $row)
        {
            $lessons[$row->id] = $row->name;
        }

        return $lessons;
    }

    /**
     * Method to get lesson records open for subscription.
     *
     * @param   int|null  $studentId  Selected student id.
     *
     * @return  array
     *
     * @since   1.2.12
     */
    public function getOpenLessonItems(?int $studentId = null): array
    {
        // Create a new query object.
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Get the current date
        $today = date("Y-m-d");

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
                ],
                [
                    'id',
                    'name',
                    'type',
                    'fee',
                    'year',
                    'state',
                    'start',
                    'min_age',
                    'max_age'
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

        $rows = $db->setQuery($query)->loadObjectlist() ?: [];
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
     * Method to get list of current lessons
     *
     * Current active lessons.
     *
     * @return array
     **/
    public function getCurrentLessons()
    {
        // Create a new query object.
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Get the current date
        $today = date("Y-m-d");

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'id',
                    'name',
                    'type',
                    'fee',
                    'year',
                    'state'
                ]
            )
        )
            ->from($db->quoteName('#__balancirk_lessons', 'a'))
            ->where($db->quote($today) . ' between `start` and `end`')
            ->order('name');

        $rows = $db->setQuery($query)->loadObjectlist();
        $lessons = [];

        foreach ($rows as $row)
        {
            $lessons[$row->id] = $row->name;
        }

        return $lessons;
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
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('DISTINCT ' . $db->quoteName('year'))
            ->from($db->quoteName('#__balancirk_lessons_complete'))
            ->order($db->quoteName('year') . ' DESC');

        $db->setQuery($query);
        return $db->loadColumn();
    }

    /**
     * Returns a student's birthdate.
     *
     * @param   int  $studentId  Student id.
     *
     * @return  string|null
     *
     * @since   1.2.12
     */
    private function getStudentBirthdate(int $studentId): ?string
    {
        if ($studentId <= 0)
        {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('birthdate'))
            ->from($db->quoteName('#__balancirk_students'))
            ->where($db->quoteName('id') . ' = ' . (int) $studentId);

        $db->setQuery($query);

        $birthdate = $db->loadResult();

        return $birthdate !== null ? (string) $birthdate : null;
    }
}
