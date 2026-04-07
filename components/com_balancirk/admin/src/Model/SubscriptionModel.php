<?php

/**
 * @package Joomla.Admin
 * @subpackage com_balancirk
 *
 * @copyright Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Model;

\defined('_JEXEC') or die;

use Exception;
use CoCoCo\Component\Balancirk\Site\Helper\LessonAgeHelper;
use CoCoCo\Component\Balancirk\Site\Helper\SubscriptionMailHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Mail\MailerFactoryInterface;
use CoCoCo\Component\Balancirk\Administrator\Model\StudentModel;
use CoCoCo\Component\Balancirk\Administrator\Model\PresencesModel;

/**
 * Subscription model for the Joomla Balancirk component.
 *
 * @since 0.0.1
 */
class SubscriptionModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var	string
     * @since  0.0.1
     */
    public $typeAlias = 'com_balancirk.subscription';

    /**
     * The prefix to use with controller messages.
     *
     * @var	string
     * @since  0.0.1
     */
    protected $textPrefix = 'COM_BALANCIRK';

    /**
     * Method to test whether a record can be deleted.
     *
     * Record can only be deleted is there are no entries in the presences table and it is the primary parent
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canDelete($record)
    {
        // If admin or if parent of student and less than 3 presences
        if (!empty($record->lesson) || !empty($record->student))
        {
            $app = Factory::getApplication();
            $parentid = $app->getIdentity()->id;

            /** @var StudentModel */
            $studentModel = $this->getMVCFactory()->createModel('Students', 'Admin');
            /** @var PresencesModel */
            $presencesModel = $this->getMVCFactory()->createModel('Presence', 'Admin');

            return ($studentModel->isPrimairyParent($parentid, $record->student) &&
                ($presencesModel->numberOfPresences($record->student, $record->lesson) <= 2) &&
                $app->getIdentity()->authorise('core.delete', 'com_balancirk.subscription.' . (int) $record->id)
            );
        }

        return false;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name	  The table name. Optional.
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
        $name = 'subscriptions';
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
     * @param	array   $data	    Data from the form.
     * @param	boolean $loadData   True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   0.0.1
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'subscription', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form))
        {
            return false;
        }

        return $form;
    }

    /**
     * Add subscription to the database
     *
     * Add if not exists the subscription to the database
     *
     * @param 	array $data array of subscriptions to
     *
     * @return 	boolean
     *
     * @version	__BUMP_VERSION__
     **/
    public function add(?array $data = null)
    {
        $values = array();
        array_push($values, $data['student']);
        array_push($values, $data['lesson']);

        // Check ik max numbers of students is not reached, if not subscribed == 0 else subscribed == 1
        /** @var lessonModel*/
        $model = $this->getMVCFactory()->createModel('Lesson', 'Site');
        $lesson = $model->getItem($data['lesson'], $data['lesson']);

        if (!$lesson || !$this->isStudentEligibleForLesson((int) $data['student'], $lesson)) {
            $this->setError(Text::_('COM_BALANCIRK_SUBSCRIPTION_AGE_MISMATCH'));

            return false;
        }

        $waitinglist = ($model->getNumberOfStudents($data['lesson']) < $lesson->max_students) ? 0 : 1;
        array_push($values, $waitinglist);

        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->insert($db->quoteName('#__balancirk_subscriptions'))
            ->columns($db->quoteName(array('student', 'lesson', 'subscribed')))
            ->values(implode(',', $values));
        $db->setQuery($query)->execute();

        /** @var StudentModel */
        $studentModel = $this->getMVCFactory()->createModel('Student', 'Site');
        $student = $studentModel->getItem((int) $data['student']);
        $parents = $studentModel->getParents($data['student']);
        /** @var MemberModel */
        $memberModel = $this->getMVCFactory()->createModel('Member', 'Site');
        $mailDefaults = $this->getSubscriptionMailDefaults();
        $subscriptionDate = date('Y-m-d');

        foreach ($parents as $parent)
        {
            $member = $memberModel->getItem($parent->parent);

            if (!$student || !$member || empty($member->email)) {
                continue;
            }

            $message = SubscriptionMailHelper::buildMailMessage(
                $lesson,
                $student,
                $member,
                $subscriptionDate,
                (bool) $waitinglist,
                $mailDefaults
            );
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer->setSender('info@balancirk.be', 'Circusatelier Balancirk VZW')
                ->addRecipient($member->email)
                ->setSubject($message['subject'])
                ->setBody($message['body'])
                ->Send();
        }

        return true;
    }

    /**
     * Check if a student fits the lesson age category.
     *
     * @param   int     $studentId  Student id.
     * @param   object  $lesson     Lesson record.
     *
     * @return  bool
     *
     * @since   1.2.12
     */
    private function isStudentEligibleForLesson(int $studentId, object $lesson): bool
    {
        /** @var StudentModel */
        $studentModel = $this->getMVCFactory()->createModel('Student', 'Admin');
        $student = $studentModel->getItem($studentId);

        if (!$student) {
            return false;
        }

        return LessonAgeHelper::matchesLesson($student->birthdate ?? null, $lesson);
    }

    /**
     * Get the configured default mail templates for subscriptions.
     *
     * @return  array<string, string>
     *
     * @since   1.2.20
     */
    private function getSubscriptionMailDefaults(): array
    {
        $params = ComponentHelper::getParams('com_balancirk');

        return [
            'subscription_subject' => (string) $params->get('email_subject_subscription', ''),
            'subscription_body' => (string) $params->get('email_body_subscription', ''),
            'waitinglist_subject' => (string) $params->get('email_subject_waitinglist', ''),
            'waitinglist_body' => (string) $params->get('email_body_waitinglist', ''),
        ];
    }

    /**
     * Delete subscription to the database
     *
     * Delete if not exists the subscription to the database
     *
     * @param   array  $id  An id of an subscription
     *
     * @return 	boolean
     *
     * @version	__BUMP_VERSION__
     **/
    public function delete(&$id)
    {
        $subscription = $this->getItem($id);

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->delete($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('student') . ' = ' . $subscription->student)
            ->where($db->quoteName('lesson') . ' = ' . $subscription->lesson);
        $db->setQuery($query)->execute();

        return true;
    }

    /**
     * Method to get a single record, but with all fields.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  CMSObject|boolean  Object on success, false on failure.
     *
     * @since   __BUMP_VERIONS__
     */
    public function getItemFull($pk = null)
    {
        if ($pk === null)
        {
            $pk = $this->getState('subscription.id');
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $db->quoteName(
                [
                    'a.id',
                    'a.studentid',
                    'a.name',
                    'a.firstname',
                    'a.lessonid',
                    'a.lesson',
                    'a.type',
                    'a.fee',
                    'a.year',
                    'a.start',
                    'a.end',
                    'a.start_registration',
                    'a.end_registration',
                    'a.state',
                    'a.subscribed'
                ],
                [
                    'id',
                    'studentid',
                    'name',
                    'firstname',
                    'lessonid',
                    'lesson',
                    'type',
                    'fee',
                    'year',
                    'start',
                    'end',
                    'start_registration',
                    'end_registration',
                    'state',
                    'subscribed'
                ]
            )
        )
            ->from($db->quoteName('#__balancirk_subscriptions_view', 'a'))
            ->where($db->quoteName('a.id') . ' = ' . $db->quote($pk));

        // check if the user is allowed to see all students or is the parent of the student
        $this->canDo = ContentHelper::getActions('com_balancirk');
        if (! $this->canDo->get('students.viewall'))
        {
            $query->join(
                'INNER',
                $db->quoteName('#__balancirk_parents', 'p'),
                'a.studentid = p.child AND p.parent = ' . Factory::getApplication()->getIdentity()->id
            );
        }

        $db->setQuery($query);

        return $db->loadObject();
    }
}
