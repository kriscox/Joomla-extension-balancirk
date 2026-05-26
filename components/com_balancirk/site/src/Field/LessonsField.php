<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\site\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use CoCoCo\Component\Balancirk\site\Model\LessonsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Lesson chooser
 *
 * @since  __BUMP_VERSION__
 */
class LessonsField extends ListField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since   __BUMP_VERSION__
     */
    protected $type = 'lessons';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects
     *
     * @since   1.6
     */
    protected function getOptions()
    {
        /** @var LessonsModel */
        $lessonModel = new LessonsModel(['OpenSubscriptions' => true]);
        $studentId = (int) $this->form->getValue('student');

        if ($studentId <= 0) {
            return parent::getOptions();
        }

        $lessons = $lessonModel->getOpenLessons($studentId);

        if (null == $lessons) {
            $lessons = [];
        }

        $lesOption = [];

        foreach ($lessons as $id => $lesson) {
            array_push(
                $lesOption,
                array('value' => $id, 'text' => $lesson)
            );
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $lesOption);

        return $options;
    }
}
