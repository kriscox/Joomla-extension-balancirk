<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\View\Subscription;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML subscription view class for the balancirk component.
 *
 * @since  0.0.1
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of students
     *
     * @var  array
     */
    protected $students;

    /**
     * The list of lessons currently open to subscription
     *
     * @var  array
     */
    protected $lessons;

    /**
     * Whether there are lessons currently open for subscription.
     *
     * @var  bool
     */
    protected $hasOpenLessons;

    /**
     * The model state
     *
     * @var  object
     */
    protected $state;

    /**
     * The actions the user is authorised to perform
     *
     * @var  \JObject
     */
    protected $canDo;

    /**
     * The \JForm object
     *
     * @var  \JForm
     */
    protected $form;

    /**
     * Open lessons keyed by student id.
     *
     * @var  array
     */
    protected $lessonsByStudent;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        $this->students = $this->get('Students');
        $this->lessons = $this->get('Lessons');
        $this->hasOpenLessons = (bool) $this->get('HasOpenLessons');
        $this->form = $this->get('Form');

        $this->lessonsByStudent = [];
        $subscriptionModel = $this->getModel();
        foreach ($this->students as $student) {
            $this->lessonsByStudent[(int) $student->id] = $subscriptionModel->getLessonsForStudent((int) $student->id);
        }

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        return parent::display($tpl);
    }
}
