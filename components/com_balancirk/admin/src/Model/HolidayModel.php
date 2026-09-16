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

use CoCoCo\Component\Balancirk\Site\Helper\SchoolYearHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Item model for Holiday.
 *
 * @since  1.2.9
 */
class HolidayModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  1.2.9
     */
    public $typeAlias = 'com_balancirk.holiday';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  1.2.9
     */
    protected $text_prefix = 'COM_BALANCIRK';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.2.9
     */
    protected function canDelete($record)
    {
        if (empty($record->id)) {
            return false;
        }

        $user = Factory::getApplication()->getIdentity();

        return $user->authorise('core.delete', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk');
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record.
     *                   Defaults to the permission set in the component.
     *
     * @since   1.2.9
     */
    protected function canEditState($record)
    {
        $user = Factory::getApplication()->getIdentity();

        // Check for existing article.
        if (!empty($record->id)) {
            return $user->authorise(
                'core.edit.state',
                'com_balancirk.holiday.' . (int) $record->id
            );
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
     * @since   1.2.9
     * @throws  \Exception
     */
    public function getTable($name = '', $prefix = '', $options = array())
    {
        $name = 'holidays';
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
     * @since   1.2.9
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'holiday', ['control' => 'jform', 'load_data' => $loadData]);

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
     * @since   1.2.9
     */
    protected function loadFormData()
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $data = $app->getUserState('com_balancirk.edit.holiday.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        $isNew = $this->holidayId($data) === 0;
        $storedYear = $this->holidayYear($data);
        $latestLessonYear = null;

        if ($isNew && SchoolYearHelper::isEmptyYear($storedYear)) {
            $latestLessonYear = $this->getLatestLessonSchoolYear();
        }

        $this->setHolidayYear(
            $data,
            SchoolYearHelper::defaultHolidayYear($storedYear, $isNew, $latestLessonYear)
        );

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    /**
     * Latest school year that already has lesson rows, or null.
     *
     * Reads `#__balancirk_lessons`. Does not invent a calendar year when
     * the table is empty.
     *
     * @return  int|null
     *
     * @since   1.3.24
     */
    private function getLatestLessonSchoolYear(): ?int
    {
        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('year') . ')')
                ->from($db->quoteName('#__balancirk_lessons'));
            $year = (int) $db->setQuery($query)->loadResult();

            return $year > 0 ? $year : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * Holiday id from form data.
     *
     * @param   mixed  $data  Item object or array.
     *
     * @return  int
     *
     * @since   1.3.24
     */
    private function holidayId(mixed $data): int
    {
        if (is_array($data)) {
            return (int) ($data['id'] ?? 0);
        }

        if (is_object($data)) {
            return (int) ($data->id ?? 0);
        }

        return 0;
    }

    /**
     * Holiday year from form data.
     *
     * @param   mixed  $data  Item object or array.
     *
     * @return  mixed
     *
     * @since   1.3.24
     */
    private function holidayYear(mixed $data): mixed
    {
        if (is_array($data)) {
            return $data['year'] ?? null;
        }

        if (is_object($data)) {
            return $data->year ?? null;
        }

        return null;
    }

    /**
     * Bind the holiday year, using an empty string when there is no default.
     *
     * @param   mixed     $data  Item object or array.
     * @param   int|null  $year  Year to show, or null for an empty field.
     *
     * @return  void
     *
     * @since   1.3.24
     */
    private function setHolidayYear(mixed &$data, ?int $year): void
    {
        $value = $year === null ? '' : $year;

        if (is_array($data)) {
            $data['year'] = $value;

            return;
        }

        if (is_object($data)) {
            $data->year = $value;
        }
    }
}
