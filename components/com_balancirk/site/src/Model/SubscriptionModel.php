<?php

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Exception;
use RuntimeException;
use CoCoCo\Component\Balancirk\Site\Helper\AccountingExportHelper;
use CoCoCo\Component\Balancirk\Site\Helper\LessonAgeHelper;
use CoCoCo\Component\Balancirk\Site\Helper\SubscriptionMailHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Mail\MailerFactoryInterface;
use CoCoCo\Component\Balancirk\Site\Model\StudentModel;

// use CoCoCo\Component\Balancirk\Site\Model\PresenceModel;

/**
 * Subscription model for the Joomla Balancirk component.
 *
 * @since  0.0.1
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
        if (!empty($record->lesson) || !empty($record->student))
        {
            $app = Factory::getApplication();
            $parentid = $app->getIdentity()->id;

            /** @var StudentModel */
            $studentModel = $this->getMVCFactory()->createModel('Students', 'Site');
            // /** @var PresenceModel */
            // $presenceModel = $this->getMVCFactory()->createModel('Presence', 'Site');

            return ($studentModel->isPrimairyParent($parentid, $record->student) &&
                // ($presenceModel->numberOfPresences($record->student, $record->lesson) <= 2) &&
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
     * Method to get the students from the user.
     *
     * @return  array  An array with all students
     *
     * @since   __BUMP_VERSION__
     */
    public function getStudents()
    {
        /** @var studentsModel */
        $model = $this->getMVCFactory()->createModel('Students', 'Site');

        return $model->getItems();
    }

    /**
     * Method to get the lessons which are open
     *
     * @return  array	An array with all lessons which are open to subscribe
     *
     * @since   __BUMP_VERSION__
     */
    public function getLessons()
    {
        /** @var lessonsModel */
        $model = $this->getMVCFactory()->createModel('Lessons', 'Site');
        $data = $this->loadFormData();
        $studentId = (int) ($data['student'] ?? 0);

        if ($studentId <= 0) {
            return [];
        }

        return $model->getOpenLessons($studentId);
    }

    /**
     * Check whether there are lessons with an open registration period.
     *
     * @return  bool
     *
     * @since   1.2.12
     */
    public function getHasOpenLessons(): bool
    {
        /** @var lessonsModel */
        $model = $this->getMVCFactory()->createModel('Lessons', 'Site');

        return count($model->getOpenLessons()) > 0;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array
     *
     * @since   1.2.12
     */
    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = (array) $app->getUserState('com_balancirk.subscription.data', array());
        $selectedStudent = $app->input->getInt('student_id');

        if ($selectedStudent > 0) {
            $data['student'] = $selectedStudent;
            unset($data['lesson']);
        }

        return $data;
    }

    /**
     * Method to get open lessons for a specific student, excluding existing subscriptions.
     *
     * @param   int  $studentId  Student id.
     *
     * @return  array
     */
    public function getLessonsForStudent(int $studentId): array
    {
        /** @var lessonsModel */
        $model = $this->getMVCFactory()->createModel('Lessons', 'Site');
        $openLessons = $model->getOpenLessons();

        if ($studentId <= 0 || empty($openLessons)) {
            return $openLessons;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('lesson'))
            ->from($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('student') . ' = ' . (int) $studentId);

        $subscribedLessonIds = array_map('intval', $db->setQuery($query)->loadColumn());

        foreach ($subscribedLessonIds as $lessonId) {
            unset($openLessons[$lessonId]);
        }

        return $openLessons;
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
     * Delete subscription to the database
     *
     * Delete if not exists the subscription to the database
     *
     * @param   array  $pks  An array of record primary keys.
     *
     * @return 	boolean
     *
     * @version	__BUMP_VERSION__
     **/
    public function delete(&$pks)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('student') . ' = ' . $pks['student'])
            ->where($db->quoteName('lesson') . ' = ' . $pks['lesson']);
        $db->setQuery($query)->execute();

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
        $studentModel = $this->getMVCFactory()->createModel('Student', 'Site');
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
     * Build the subscription export for accounting.
     *
     * @param   string|null  $year    School year filter.
     * @param   string       $format  Export format.
     *
     * @return  array{content:string,filename:string,mimeType:string}
     *
     * @since   1.2.22
     */
    public function exportForAccounting(?string $year, string $format = 'csv'): array
    {
        $rows = $this->getAccountingExportRows($year);
        $safeYear = $year !== null && $year !== '' ? preg_replace('/[^0-9A-Za-z_-]/', '', $year) : 'all';

        if ($format === 'xls') {
            return [
                'content' => AccountingExportHelper::renderXls($rows),
                'filename' => 'balancirk-subscriptions-' . $safeYear . '.xls',
                'mimeType' => 'application/vnd.ms-excel; charset=utf-8',
            ];
        }

        return [
            'content' => AccountingExportHelper::renderCsv($rows),
            'filename' => 'balancirk-subscriptions-' . $safeYear . '.csv',
            'mimeType' => 'text/csv; charset=utf-8',
        ];
    }

    /**
     * Load the accounting export rows.
     *
     * @param   string|null  $year  School year filter.
     *
     * @return  array<int, array<string, string>>
     *
     * @since   1.2.22
     */
    public function getAccountingExportRows(?string $year = null): array
    {
        if (!Factory::getApplication()->getIdentity()->authorise('accounting.export', 'com_balancirk')) {
            throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $addressExpression = "TRIM(CONCAT_WS(' ', NULLIF(" . $db->quoteName('m.street') . ", ''), NULLIF(" . $db->quoteName('m.number') . ", '')))";
        $parentJoin = $db->quoteName('#__balancirk_parents', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ('
            . 'SELECT ' . $db->quoteName('pp.id')
            . ' FROM ' . $db->quoteName('#__balancirk_parents', 'pp')
            . ' WHERE ' . $db->quoteName('pp.child') . ' = ' . $db->quoteName('s.id')
            . ' ORDER BY ' . $db->quoteName('pp.primary') . ' DESC, ' . $db->quoteName('pp.id') . ' ASC'
            . ' LIMIT 1'
            . ')';

        $query->select($db->quoteName('m.firstname', 'firstname'))
            ->select($db->quoteName('m.name', 'name'))
            ->select($addressExpression . ' AS ' . $db->quoteName('address'))
            ->select($db->quoteName('m.bus', 'bus'))
            ->select($db->quoteName('m.postcode', 'postcode'))
            ->select($db->quoteName('m.city', 'city'))
            ->select($db->quoteName('m.email', 'email'))
            ->select($db->quoteName('l.name', 'lesson'))
            ->select($db->quoteName('s.firstname', 'student_firstname'))
            ->select($db->quoteName('s.name', 'student_name'))
            ->select($db->quoteName('s.uitpas', 'uitpas'))
            ->select($db->quoteName('s.mutuality', 'mutuality'))
            ->from($db->quoteName('#__balancirk_subscriptions', 'sub'))
            ->join('INNER', $db->quoteName('#__balancirk_lessons', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('sub.lesson'))
            ->join('INNER', $db->quoteName('#__balancirk_students', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('sub.student'))
            ->join('LEFT', $parentJoin)
            ->join('LEFT', $db->quoteName('#__balancirk_members', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('p.parent'))
            ->where($db->quoteName('sub.subscribed') . ' = 0')
            ->order($db->quoteName('m.name') . ' ASC, ' . $db->quoteName('m.firstname') . ' ASC, ' . $db->quoteName('s.name') . ' ASC, ' . $db->quoteName('s.firstname') . ' ASC, ' . $db->quoteName('l.name') . ' ASC');

        if ($year !== null && $year !== '') {
            $query->where($db->quoteName('l.year') . ' = :year')
                ->bind(':year', $year);
        }

        $db->setQuery($query);

        return array_map(
            static fn(array $row): array => AccountingExportHelper::normalizeRow($row),
            $db->loadAssocList()
        );
    }
}
