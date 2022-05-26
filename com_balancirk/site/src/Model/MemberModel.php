<?php

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;
use Joomla\CMS\Language\Text;
//use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

/**
 * Member Model
 * @since __BUMP_VERSION__
 */
class MemberModel extends ListModel
{
    /**
     * @var member
     */
    protected $member;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     \JController
     * @since   __BUMP_VERSION__
     */
    public function __construct($config = array())
    {
        /* if (empty($config['filter_fields'])) {
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
        } */

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
     * @since   __BUMP_VERSION__
     */
    public function getTable($type = 'MemberTable', $prefix = 'Balancirk', $config = null)
    {
        return JTable::getInstance($type, $prefix, $config);
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
                    'p.id', 'a.firstname', 'p.name', 'p.phone', 'p.email'
                )
            )
        );

        $query->from($db->quoteName($this->getTable(), 'a'));

        // Join with users (parent is joomla-user)
        $query->join(
            'INNER',
            $db->quoteName('#__users', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.id')
        );

        return $query;
    }

    /**
     * Get member information from table to be shown
     * 
     *  @return 
     * 
     *  @since __BUMP_VERSION__
     */
    public function getMember($id = null)
    {

        $db = $this->getDbo();
        $query = $this->getListQuery();

        // Filter on the current logged-in user as parent
        if ($id === null) {
            $id = Factory::getApplication()->getIdentity();
        }

        if ($this->_item === null) {
            $this->_item = array();
        }

        if (!isset($this->_item[$id])) {
            try {
                if ($id != null) {
                    $query->where($db->quoteName('p.id') . ' = ' . $db->quote($id));
                }
                $db->setQuery($query);
                $data = $db->loadObject();
                if (empty($data)) {
                    throw new \Exception(Text::_('COM_BALANCIRK_ERROR_MEMBER_NOT_FOUND'), 404);
                }
                $this->_item[$id] = $data;
            } catch (\Exception $e) {
                $this->_item[$id] = false;
                throw $e;
            }
        }
        return $this->_item[$id];
    }
}
