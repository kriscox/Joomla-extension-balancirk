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
use Joomla\CMS\Application\CMSApplication;

/**
 * Item model for lesson.
 *
 * @since  0.0.1
 */
class LessonModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  0.0.1
     */
    public $typeAlias = 'com_balancirk.lesson';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  0.0.1
     */
    protected $text_prefix = 'COM_BALANCIRK';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canDelete($record)
    {
        if (!empty($record->id)) {
            $app = Factory::getApplication();

            return $app->getIdentity()->authorise('core.delete');
        }

        return false;
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canEditState($record)
    {
        $user = Factory::getApplication()->getIdentity();

        // Check for existing article.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state');
        }

        // Default to component settings if neither article nor category known.
        return parent::canEditState($record);
    }

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
        $name = 'lessons';
        $prefix = 'Table';

        if ($table = $this->_createTable($name, $prefix, $options)) {
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
        $form = $this->loadForm($this->typeAlias, 'lesson', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   0.0.1
     */
    protected function loadFormData()
    {
        /** @var CMSApplication $app*/
        $app = Factory::getApplication();
        $data = $app->getUserState('com_balancirk.edit.lesson.data', array());

        if (empty($data)) {
            $data = $this->getItem();

            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
        }

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    /**
     * Load individual lessons with date and hour
     *
     * @param   int $lesson is the id of the lesson
     *
     * @return  array of hours
     *
     * @since   0.0.1
     */
    public function getHours(int $lesson = null)
    {
        // Don't know if it works
        $lesson = (!is_null($lesson) ? $lesson : (int) $this->getState('lesson.id'));

        // Get the database connection
        $db = $this->getDatabase();

        $query = $db->getQuery(true);

        // Get all hours for the current lesson
        $query->select($db->quoteName(array('id', 'day')))
            ->from($db->quoteName('#__balancirk_hours'))
            ->where($db->quoteName('lesson') . ' = ' . $lesson);

        return $db->loadRowList();
    }

    /**
     * Method to get lesdays of timyint as an array
     *
     * @param int lesdays Number representing days of lesson.
     *
     * @return string	string with the values of the days
     **/
    public static function getLesdays($lesdays)
    {
        $returnvalue = "";
        $returnvalue .= (64 == (64 & $lesdays) ? "64, " : "");
        $returnvalue .= (32 == (32 & $lesdays) ? "32, " : "");
        $returnvalue .= (16 == (16 & $lesdays) ? "16, " : "");
        $returnvalue .= (8 == (8 & $lesdays) ? "8, " : "");
        $returnvalue .= (4 == (4 & $lesdays) ? "4, " : "");
        $returnvalue .= (2 == (2 & $lesdays) ? "2, " : "");
        $returnvalue .= (1 == (1 & $lesdays) ? "1" : "");
        return $returnvalue;
    }
}
