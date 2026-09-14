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
 * Lesson chooser for administrator enrolments.
 *
 * @since  1.3.21
 */
class LessonsField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.3.21
     */
    protected $type = 'Lessons';

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
            ->select($db->quoteName(['id', 'name', 'year']))
            ->from($db->quoteName('#__balancirk_lessons'))
            ->order($db->quoteName('year') . ' DESC, ' . $db->quoteName('name') . ' ASC');

        $lessons = $db->setQuery($query)->loadObjectList() ?: [];
        $options = [HTMLHelper::_('select.option', '', '-')];

        foreach ($lessons as $lesson) {
            $label = trim((string) $lesson->name);

            if (!empty($lesson->year)) {
                $label .= ' (' . $lesson->year . ')';
            }

            $options[] = HTMLHelper::_('select.option', (int) $lesson->id, $label);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
