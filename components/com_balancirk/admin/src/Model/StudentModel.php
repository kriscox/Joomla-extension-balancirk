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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Jooma\CMS\CMSApplicationInterface;

/**
 * Item model for student.
 *
 * @since  0.0.1
 */
class StudentModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  0.0.1
     */
    public $typeAlias = 'com_balancirk.student';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  0.0.1
     */
    protected $text_prefix = 'COM_BALANCIRK';

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   0.0.1
     * @throws  \Exception
     */
    public function getTable($name = '', $prefix = '', $options = array())
    {
        $name = 'students';
        $prefix = 'Table';

        if ($table = $this->_createTable($name, $prefix, $options))
        {
            return $table;
        }

        throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name), 0);
    }

    /**
     * Method to get the row form.
     *
     * @param   array   $data       Data from the form.
     * @param   boolean $loadData   True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   0.0.1
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'student', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form))
        {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since  0.0.1
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        /** @var CMSApplicationInterface */
        $app = Factory::getApplication();
        $data = $app->getUserState('com_balancirk.edit.student.data', array());



        if (empty($data))
        {
            $data = $this->getItem();

            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
        }

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    /**
     * Method to change the published state of one or more records.
     *
     * @param   array    $pks    A list of the primary keys to change.
     * @param   integer  $value  The value of the published state.
     *
     * @return  void  True on success.
     *
     * @since   0.0.1
     */
    public function publish(&$pks, $value = 1)
    {
        // This is a very simple method to change the state of each item selected
        $db = $this->getDatabase();

        $query = $db->getQuery(true);

        $query->update('`#__balancirk_students`');
        $query->set('state = ' . $value);
        $query->where('id IN (' . implode(',', $pks) . ')');
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Method to get the parents name and phone number of a student
     *
     * @param	int	$student	student id
     *
     * @return  array  An array with all parents
     *
     * @since   __BUMP_VERSION__
     */
    public function getParents()
    {
        $db     = $this->getDatabase();
        $query     = $db->getQuery(true);
        $query->select($db->quoteName(['m.id', 'm.name', 'm.firstname', 'm.phone']))
            ->from($db->quoteName('#__balancirk_parents', 'p'))
            ->join('INNER', $db->quoteName('#__balancirk_members', 'm') .
                ' ON ' .  $db->quoteName('p.parent') . ' = ' . $db->quoteName('m.id'))
            ->where($db->quoteName('p.child') . ' = ' . $this->getState('student.id'));

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Check if parent is primairy
     *
     * Check if the given parent id is the primairy parent of the student.
     *
     * @param	int	$parent		Parent id
     * @param	int	$student	Student id
     *
     * @return	boolean	true if it is the primary parent id in other cases false
     *
     * @since __BUMP_VERSION__
     */
    public function isPrimairyParent(int $parent = null, int $student = null)
    {
        // Check if the user is the primary parent of the student
        $db     = $this->getDatabase();
        $query     = $db->getQuery(true);
        $query->select($db->quote('*'))
            ->from($db->quoteName('#__balancirk_parents'))
            ->where($db->quoteName('child') . ' = ' . $db->quote($student))
            ->where($db->quoteName('parent') . ' = ' . $db->quote($parent))
            ->where($db->quoteName('primary') . ' = 1');

        if ($db->setQuery($query)->execute())
        {
            return $db->getNumRows() >= 1;
        }
        else
        {
            return false;
        }
    }
}
