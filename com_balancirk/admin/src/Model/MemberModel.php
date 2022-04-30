<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo, Inc.
 * @license     GNU General Public License version 3
 */

namespace CoCoCo\Component\Balancirk\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Item Model for a Studen.
 *
 * @since  __BUMP_VERSION__
 */
class MemberModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  __BUMP_VERSION__
     */
    public $typeAlias = 'com_balancirk.member';

    /**
     * Method to get the row form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   __BUMP_VERSION__
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'member', ['control' => 'jform', 'load_data' => $loadData]);
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
     * @since   __BUMP_VERSION__
     */
    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $this->getItem();
        $this->preprocessData($this->typeAlias, $data);
        return $data;
    }
    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   \Joomla\CMS\Table\Table  $table  The Table object
     *
     * @return  void
     *
     * @since   __BUMP_VERSION__
     */
    protected function prepareTable($table)
    {
        $table->generateAlias();
    }

    /**
     * Return all fieldnames
     *
     * @return  list
     *
     * @since   __BUMP_VERSION__
     */
    protected function getFieldnames()
    {
        return $this->fieldset;
    }
}
