<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Field;

use Joomla\CMS\Form\Field\ListField;
use CoCoCo\Component\Balancirk\Site\Model\StudentsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Student chooser
 *
 * @since  __BUMP_VERSION__
 */
class StudentsField extends ListField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since   __BUMP_VERSION__
     */
    protected $type = 'students';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects
     *
     * @since   1.6
     */
    protected function getOptions()
    {
        /** @var studentsModel */
        $studentsModel = new StudentsModel();
        $myStudents = $studentsModel->getItems();
        $studentOption = [];

        if (null == $myStudents) {
            $myStudents = [];
        } else {
            foreach ($myStudents as $student) {
                array_push(
                    $studentOption,
                    array('value' => $student->id, 'text' => $student->firstname . ' ' . $student->name)
                );
            }
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $studentOption);

        return $options;
    }
}
