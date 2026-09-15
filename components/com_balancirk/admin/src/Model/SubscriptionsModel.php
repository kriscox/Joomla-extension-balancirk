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
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Helper\ContentHelper;
use CoCoCo\Component\Balancirk\Site\Helper\SchoolYearHelper;

/**
 * SubscriptionsModel class to display the list of subscriptions.
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
     * @since   __BUMP_VERSION__
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'name',
                'a.name',
                'firstname',
                'a.firstname',
                'lesson',
                'a.lesson',
                'year',
                'a.year',
                'subscribed',
                'a.subscribed',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.3.21
     */
    protected function populateState($ordering = 'a.name', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        parent::populateState($ordering, $direction);
        $this->initialiseYearFilter();
    }

    /**
     * Default the year filter to the current school year and persist it.
     *
     * @return  void
     */
    private function initialiseYearFilter(): void
    {
        $year = SchoolYearHelper::formFilterYear($this->state->get('filter.year'));
        $this->setState('filter.year', $year);
        SchoolYearHelper::persistListFilterYear(Factory::getApplication(), $this->context, $year);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string
     *
     * @since   1.3.21
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.year');

        return parent::getStoreId($id);
    }

    /**
     * Inject the resolved year into the searchtools filter form.
     *
     * @return  mixed
     */
    protected function loadFormData()
    {
        $data = parent::loadFormData();
        $year = (string) $this->getState('filter.year', '');

        if ($year === '') {
            return $data;
        }

        if (is_object($data)) {
            $filter = $data->filter ?? [];

            if (is_object($filter)) {
                $filter->year = $year;
                $data->filter = $filter;
            } else {
                $filter = (array) $filter;
                $filter['year'] = $year;
                $data->filter = $filter;
            }
        } elseif (is_array($data)) {
            $data['filter'] = (array) ($data['filter'] ?? []);
            $data['filter']['year'] = $year;
        }

        return $data;
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
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.studentid',
                    'a.name',
                    'a.firstname',
                    'a.lessonid',
                    'a.lesson',
                    'a.type',
                    'a.fee',
                    'a.year',
                    'a.start',
                    'a.end',
                    'a.start_registration',
                    'a.end_registration',
                    'a.state',
                    'a.subscribed',
                ],
                [
                    'id',
                    'studentid',
                    'name',
                    'firstname',
                    'lessonid',
                    'lesson',
                    'type',
                    'fee',
                    'year',
                    'start',
                    'end',
                    'start_registration',
                    'end_registration',
                    'state',
                    'subscribed',
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_subscriptions_view', 'a'));

        $parentId = (int) $this->getState('filter.parent_id', 0);

        if ($parentId > 0) {
            $query->join(
                'INNER',
                $db->quoteName('#__balancirk_parents', 'p'),
                'a.studentid = p.child AND p.parent = ' . $parentId
            );
        } else {
            $this->canDo = ContentHelper::getActions('com_balancirk');

            if (!$this->canSeeAllSubscriptions()) {
                $query->join(
                    'INNER',
                    $db->quoteName('#__balancirk_parents', 'p'),
                    'a.studentid = p.child AND p.parent = ' . (int) Factory::getApplication()->getIdentity()->id
                );
            }
        }

        // Filter by school year — default to the current school year.
        $selectedYear = SchoolYearHelper::resolveListFilterYear($this->getState('filter.year'));

        if ($selectedYear !== null) {
            $query->where($db->quoteName('a.year') . ' = ' . $db->quote($selectedYear));
        }

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where(
                '(a.name LIKE ' . $search
                . ' OR a.firstname LIKE ' . $search
                . ' OR a.lesson LIKE ' . $search . ')'
            );
        }

        $orderCol = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Get distinct school years from subscriptions for the filter dropdown.
     *
     * @return  array
     *
     * @since   1.3.21
     */
    public function getYears(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('year'))
            ->from($db->quoteName('#__balancirk_subscriptions_view'))
            ->order($db->quoteName('year') . ' DESC');

        return SchoolYearHelper::normaliseYearList($db->setQuery($query)->loadColumn() ?: []);
    }

    /**
     * Whether the current user may list all subscriptions.
     *
     * @return  boolean
     *
     * @since   1.3.21
     */
    private function canSeeAllSubscriptions(): bool
    {
        $canDo = $this->canDo ?? ContentHelper::getActions('com_balancirk');

        return $canDo->get('students.viewall')
            || $canDo->get('lessons.admin')
            || $canDo->get('subscriptions.create')
            || $canDo->get('subscriptions.delete')
            || $canDo->get('core.admin');
    }
}
