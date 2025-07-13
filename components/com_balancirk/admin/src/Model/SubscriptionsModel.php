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
 * SubscriptionsModel class to display the list off Subscriptions.
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
                    'a.subscribed'
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
                    'subscribed'
                ]
            )
        );
        $query->from($db->quoteName('#__balancirk_subscriptions_view', 'a'));

        // Based on the user access level, we need to filter the results.
        // What Access Permissions does this user have? What can (s)he do?
        $this->canDo = ContentHelper::getActions('com_balancirk');
        if (!$this->canDo->get('students.viewall'))
        {
            $query->join('INNER', $db->quoteName('#__balancirk_parents', 'p'), 'a.studentid = p.child AND p.parent = ' . Factory::getApplication()->getIdentity()->id);
        }

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
