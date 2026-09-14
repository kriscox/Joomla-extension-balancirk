<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

/**
 * Student chooser for administrator enrolments.
 *
 * @since  1.3.21
 */
class StudentsField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.3.21
     */
    protected $type = 'Students';

    /**
     * Method to get the field options.
     *
     * @return  array
     *
     * @since   1.3.21
     */
    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'firstname', 'name']))
            ->from($db->quoteName('#__balancirk_students'))
            ->where('(' . $db->quoteName('state') . ' = 0 OR ' . $db->quoteName('state') . ' = 1)')
            ->order($db->quoteName('name') . ' ASC, ' . $db->quoteName('firstname') . ' ASC');

        $students = $db->setQuery($query)->loadObjectList() ?: [];
        $options = [HTMLHelper::_('select.option', '', '-')];

        foreach ($students as $student) {
            $options[] = HTMLHelper::_(
                'select.option',
                (int) $student->id,
                trim($student->firstname . ' ' . $student->name)
            );
        }

        return array_merge(parent::getOptions(), $options);
    }
}
