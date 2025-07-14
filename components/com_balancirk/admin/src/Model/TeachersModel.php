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
 * TeachersModel class to display the list off Presences.
 *
 * @since  0.0.1
 */
class TeachersModel extends ListModel
{
    protected $date;
    protected $teacher;

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
                'id',
                'a.id',
                'lesson',
                'a.lesson',
                'teacher',
                'a.teacher',
                'date',
                'a.date'
            );
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
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.lesson',
                    'a.teacher',
                    'a.date'
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_teached', 'a'));

        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get a list of presences.
     * Overridden to add a check for access levels.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   __BUMP_VERSION__
     */
    public function getItems()
    {
        // Create a new query object.
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // if date = null then take date of today
        if ($this->date == null) {
            $this->date = date("Y-m-d");
        }

        // Select the required fields from the table.
        $query->select(
            $db->quoteName(
                [
                    'teacher'
                ]
            )
        )
            ->from($db->quoteName('#__balancirk_teached', 'a'))
            ->where($db->quote($this->lesson) . ' = `lesson`')
            ->where($db->quote($this->date) . ' = `date`');

        $rows = $db->setQuery($query)->loadObjectlist();

        foreach ($rows as $row) {
            $teachers[] = $row->teacher;
        }

        return $teachers;
    }

    /**
     * Method to get the total number of presences
     *
     * @return  int  The total number of presences.
     *
     * @since   __BUMP_VERSION__
     */
    public function getTotal()
    {

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // if date = null then take date of today
        if ($this->date == null) {
            $this->date = date("Y-m-d");
        }

        // Count the number of presences
        $query->select('COUNT(*)')
            ->from($db->quoteName('#__balancirk_teached', 'a'))
            ->where($db->quote($this->lesson) . ' = `lesson`')
            ->where($db->quote($this->date) . ' = `date`');

        $total = $db->setQuery($query)->loadResult();

        return $total;
    }

    /**
     * Method to get list of presences for a specific lesson on a spefic date
     *
     * Presences of a lesson on a day.
     *
     * @param   int     $lesson  The lesson id.
     * @param   string  $date    The date.
     *
     **/
    public function getPresences($lesson = null, $date = null)
    {
        $this->lesson = $lesson;
        $this->date = $date;
    }
}
