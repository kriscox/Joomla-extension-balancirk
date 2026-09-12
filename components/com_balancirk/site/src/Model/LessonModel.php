<?php

/**
 * @package     Joomla.site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2023 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use DateTime;
use DatePeriod;
use DateInterval;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Mail\MailerFactoryInterface;
use CoCoCo\Component\Balancirk\Administrator\Model\HolidaysModel;

/**
 * LessonsModel class to display the list off lessons.
 *
 * @since  0.0.1
 */
class LessonModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var	string
     * @since  0.0.1
     */
    public $typeAlias = 'com_balancirk.lesson';

    /**
     * The prefix to use with controller messages.
     *
     * @var	string
     * @since  0.0.1
     */
    protected $textPrefix = 'COM_BALANCIRK';

    /**
     * Load a lesson and normalise start/end from the lessons table.
     *
     * The site table reads `#__balancirk_lessons_complete`. Older or broken
     * views can omit those date columns, and Joomla Table::bind() skips NULL
     * values. Attendance needs the real DATE values from `#__balancirk_lessons`.
     *
     * @param   int|null  $pk  Primary key.
     *
     * @return  object|false
     *
     * @since   1.3.20
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if (!is_object($item) || (int) ($item->id ?? 0) <= 0) {
            return $item;
        }

        $period = $this->resolveLessonPeriod($item);

        if ($period !== null) {
            $item->start = $period['start']->format('Y-m-d');
            $item->end = $period['end']->format('Y-m-d');
        }

        return $item;
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
        $form = $this->loadForm($this->typeAlias, 'lesson', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the presence row form.
     *
     * @param	array   $data	    Data from the form.
     * @param	boolean $loadData   True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   0.0.1
     */
    public function getPresenceForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'presence', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the teachers row form.
     *
     * @param	array   $data	    Data from the form.
     * @param	boolean $loadData   True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   0.0.1
     */
    public function getTeacherForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'teacher', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the presences of this lesson
     *
     * List of the number of students present per lesday for this lesson
     *
     * @return array 	an array of students and dates
     */
    public function getPresences()
    {
        // Create a new query object
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);

        // Select the required fields from the table
        $query->select(
            array(
                $dbo->quoteName('date'),
                'COUNT(' . $dbo->quoteName('student') . ') as count'
            )
        )
            ->from($dbo->quoteName('#__balancirk_presences', 'a'))
            ->where($dbo->quoteName('lesson') . ' = ' . $dbo->quote($this->getState('lesson.id')))
            ->group($dbo->quoteName('date'));

        $dbo->setQuery($query);

        return $dbo->loadObjectList();
    }

    /**
     * Method to get the studentslist
     *
     * List of the students currently subscribed to the lesson
     *
     * @param int 		$lessonid  The id of the lesson
     *
     * @return array	an array of students
     *
     **/
    public function getStudents($lessonid = null)
    {
        // Create a new query object.
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);

        $today = new DateTime('now');
        $startRange = date_sub($today, date_interval_create_from_date_string('7 days'));
        $endRange = date_sub($today, date_interval_create_from_date_string('1 days'));

        if ($lessonid == null) {
            $lessonid = $this->getState('lesson.id');
        }

        // Select the required fields from the table.
        $query->select(
            $dbo->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.firstname',
                    'a.phone',
                    'a.email',
                    'a.birthdate',
                    'a.allow_photo',
                    'a.state'
                ],
                [
                    'id',
                    'name',
                    'firstname',
                    'phone',
                    'email',
                    'birthdate',
                    'allow_photo',
                    'state'
                ]
            )
        )
            ->select('MAX(p.date) as last_presence')
            ->from($dbo->quoteName('#__balancirk_students', 'a'))
            ->join(
                'INNER',
                $dbo->quoteName('#__balancirk_subscriptions', 's') . ' ON s.student = a.id AND s.subscribed = 0'
            )
            ->join(
                'LEFT',
                $dbo->quoteName('#__balancirk_presences', 'p') . ' ON p.student = a.id AND p.lesson = s.lesson'
            )
            ->where('s.lesson = ' . $lessonid)
            ->order(['a.name', 'a.firstname'])
            ->group('a.id', 'a.name', 'a.firstname', 'a.phone', 'a.email', 'a.birthdate', 'a.allow_photo', 'a.state');

        $dbo->setQuery($query);

        return $dbo->loadObjectList();
    }

    /**
     * Method to get the waiting list for a lesson.
     *
     * List of students currently on the waiting list for the lesson.
     *
     * @param   int|null  $lessonid  The id of the lesson
     *
     * @return  array  An array of students
     */
    public function getWaitingListStudents($lessonid = null)
    {
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);

        if ($lessonid == null) {
            $lessonid = $this->getState('lesson.id');
        }

        $query->select(
            $dbo->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.firstname',
                    'a.birthdate',
                ],
                [
                    'id',
                    'name',
                    'firstname',
                    'birthdate',
                ]
            )
        )
            ->from($dbo->quoteName('#__balancirk_students', 'a'))
            ->join(
                'INNER',
                $dbo->quoteName('#__balancirk_subscriptions', 's') . ' ON s.student = a.id AND s.subscribed = 1'
            )
            ->where('s.lesson = ' . (int) $lessonid)
            ->order(['a.name', 'a.firstname']);

        $dbo->setQuery($query);

        return $dbo->loadObjectList();
    }

    /**
     * Method to get the number of students
     *
     * @return int 	number of students
     *
     */
    public function getNumberOfStudents($lessonid)
    {
        return sizeof($this->getStudents($lessonid));
    }

    /**
     * Method to get the teacherslist
     *
     * List of the teachers teaching the lesson
     *
     * @param int 		$lessonid  The id of the lesson
     *
     * @return array	an array of teachers
     *
     */
    public function getTeachers($lessonid = null)
    {
        // Create a new query object.
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);

        if ($lessonid == null) {
            $lessonid = $this->getState('lesson.id');
        }

        $lessonid = (int) $lessonid;

        if ($lessonid <= 0) {
            return [];
        }

        // Select the required fields from the table.
        $query->select(
            $dbo->quoteName(
                [
                    'a.id',
                    'a.name',
                    'a.firstname',
                    'a.phone',
                    'a.email',
                ],
                [
                    'id',
                    'name',
                    'firstname',
                    'phone',
                    'email',
                ]
            )
        )
            ->from($dbo->quoteName('#__balancirk_members', 'a'))
            ->join(
                'INNER',
                $dbo->quoteName('#__balancirk_teachers', 't') . ' ON t.member = a.id'
            )
            ->where('t.lesson = ' . $lessonid)
            ->order(['a.name', 'a.firstname']);

        $dbo->setQuery($query);

        return $dbo->loadObjectList() ?: [];
    }

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
     *
     * @since   1.6
     */
    protected function canDelete($record)
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();

        return $app->getIdentity()->authorise('lessons.admin', $this->option);
    }

    /**
     * Method to test whether a record can have its state changed.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
     *
     * @since   1.6
     */
    protected function canEditState($record)
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();

        return $app->getIdentity()->authorise('lessons.admin', $this->option);
    }

    /**
     * Method to get lesdays of timyint as an array
     *
     * @param int lesdays Number representing days of lesson.
     *
     * @return array
     **/
    public static function getLesdays($lesdays)
    {
        $returnvalue = array();
        $returnvalue["Monday"] = (64 == (64 & $lesdays) ? 1 : 0);
        $returnvalue["Tuesday"] = (32 == (32 & $lesdays) ? 1 : 0);
        $returnvalue["Wednesday"] = (16 == (16 & $lesdays) ? 1 : 0);
        $returnvalue["Thursday"] = (8 == (8 & $lesdays) ? 1 : 0);
        $returnvalue["Friday"] = (4 == (4 & $lesdays) ? 1 : 0);
        $returnvalue["Saturday"] = (2 == (2 & $lesdays) ? 1 : 0);
        $returnvalue["Sunday"] = (1 == (1 & $lesdays) ? 1 : 0);
        return $returnvalue;
    }

    /**
     * Whether a weekday bitmask has at least one selected day.
     *
     * @param   array<string, int>  $lesdays  Weekday flags from getLesdays().
     *
     * @return  bool
     *
     * @since   1.3.20
     */
    public static function hasConfiguredLesdays(array $lesdays): bool
    {
        return in_array(1, $lesdays, true);
    }

    /**
     * Whether the lesson has a usable start and end date.
     *
     * @param   mixed  $start  Lesson start date.
     * @param   mixed  $end    Lesson end date.
     *
     * @return  bool
     *
     * @since   1.3.20
     */
    public static function isValidLessonPeriod(mixed $start, mixed $end): bool
    {
        $startDate = self::parseLessonDate($start);
        $endDate = self::parseLessonDate($end);

        return $startDate instanceof DateTime && $endDate instanceof DateTime && $startDate <= $endDate;
    }

    /**
     * Resolve the lesson period from the lessons table, then from the item.
     *
     * The lessons table is the source of truth for start and end. The complete
     * view used by LessonTable can miss those columns or return unusable values.
     *
     * @param   object|null  $item  Lesson record from the view/table.
     *
     * @return  array{start: DateTime, end: DateTime}|null
     *
     * @since   1.3.20
     */
    public function resolveLessonPeriod(?object $item): ?array
    {
        $id = (int) ($item->id ?? 0);

        if ($id > 0) {
            $row = $this->loadLessonDates($id);
            $period = self::periodFromValues($row->start ?? null, $row->end ?? null);

            if ($period !== null) {
                return $period;
            }
        }

        return self::periodFromValues($item->start ?? null, $item->end ?? null);
    }

    /**
     * Load start and end from the lessons table.
     *
     * @param   int  $id  Lesson id.
     *
     * @return  object|null
     *
     * @since   1.3.20
     */
    private function loadLessonDates(int $id): ?object
    {
        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName(['start', 'end']))
                ->from($db->quoteName('#__balancirk_lessons'))
                ->where($db->quoteName('id') . ' = ' . $id);
            $row = $db->setQuery($query)->loadObject();

            return $row ?: null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * Build a period from two date values.
     *
     * @param   mixed  $start  Start date.
     * @param   mixed  $end    End date.
     *
     * @return  array{start: DateTime, end: DateTime}|null
     *
     * @since   1.3.20
     */
    public static function periodFromValues(mixed $start, mixed $end): ?array
    {
        $startDate = self::parseLessonDate($start);
        $endDate = self::parseLessonDate($end);

        if (!$startDate instanceof DateTime || !$endDate instanceof DateTime || $startDate > $endDate) {
            return null;
        }

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Parse a lesson date into a DateTime at midnight.
     *
     * Accepts ISO dates, Belgian d/m/Y variants, DateTime objects, and
     * datetime strings that start with a calendar date.
     *
     * @param   mixed  $date  Date value from the lesson record or form.
     *
     * @return  DateTime|null
     *
     * @since   1.3.20
     */
    public static function parseLessonDate(mixed $date): ?DateTime
    {
        if ($date instanceof \DateTimeInterface) {
            $parsed = DateTime::createFromInterface($date);
            $parsed->setTime(0, 0, 0);

            return $parsed;
        }

        $date = trim(html_entity_decode((string) $date, ENT_QUOTES, 'UTF-8'));
        $date = preg_replace('/\s+/', ' ', $date) ?? $date;

        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches) === 1) {
            return self::dateFromParts((int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $date, $matches) === 1) {
            return self::dateFromParts((int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d',
            'd-m-Y H:i:s',
            'd/m/Y H:i:s',
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'j-n-Y',
            'j/n/Y',
        ];

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat('!' . $format, $date);

            if (!$parsed instanceof DateTime) {
                continue;
            }

            $errors = DateTime::getLastErrors();

            if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                continue;
            }

            $parsed->setTime(0, 0, 0);

            return $parsed;
        }

        try {
            $parsed = new DateTime($date);
            $parsed->setTime(0, 0, 0);

            return $parsed;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Build a midnight DateTime from calendar parts.
     *
     * @param   int  $year   Year.
     * @param   int  $month  Month.
     * @param   int  $day    Day.
     *
     * @return  DateTime|null
     *
     * @since   1.3.20
     */
    private static function dateFromParts(int $year, int $month, int $day): ?DateTime
    {
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        $parsed = DateTime::createFromFormat('!Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));

        return $parsed instanceof DateTime ? $parsed : null;
    }

    /**
     * Method to get the dates of the lessons based on startdate, enddate, lesdays and holidays
     *
     * @param	date	$startDate	Starting date of the lessons
     * @param	date	$endDate	Ending date of the lessons
     * @param	array	$lesdays	An array of the days of the week the lessons take place
     *
     * @return array dates of the lessons
     */
    public static function getDates($start, $end, $lesday)
    {
        $endDate = (new DateTime($end))->modify('+1 day');
        $period = new DatePeriod(new DateTime($start), new DateInterval('P1D'), $endDate);
        $dates = array();

        foreach ($period as $date) {
            // TODO: Check if the date is a holiday

            // Check if date is lesday
            if ($lesday[$date->format('l')] === 1) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * Return student ids already marked present for a lesson date.
     *
     * @param   int     $lessonId  Lesson id.
     * @param   string  $date      Date in Y-m-d.
     *
     * @return  int[]
     *
     * @since   1.3.20
     */
    public function getPresentStudentIds(int $lessonId, string $date): array
    {
        if ($lessonId <= 0 || $date === '') {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('student'))
            ->from($db->quoteName('#__balancirk_presences'))
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('date') . ' = ' . $db->quote($date))
            ->order($db->quoteName('student') . ' ASC');

        return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
    }

    /**
     * Method to save the presence of the students
     *
     * @param	int		$id			Id of the lesson
     * @param	date	$date		Date of the lesson
     * @param	array	$students	An array of the students present
     *
     * @return void
     */
    public function savePresence($id, $date, $students)
    {
        $students = is_array($students) ? $students : [];
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);

        // Delete all presences for this lesson
        $query->delete($dbo->quoteName('#__balancirk_presences'))
            ->where($dbo->quoteName('lesson') . ' = ' . (int) $id)
            ->where($dbo->quoteName('date') . ' = ' . $dbo->quote($date));
        $dbo->setQuery($query);
        $dbo->execute();

        // Insert the new presences
        foreach ($students as $student) {
            $query->clear();
            $query->insert($dbo->quoteName('#__balancirk_presences'))
                ->columns($dbo->quoteName(['lesson', 'student', 'date']))
                ->values($id . ', ' . $student . ', ' . $dbo->quote($date));
            $dbo->setQuery($query);
            $dbo->execute();
        }
    }

    /**
     * Return teacher ids already marked as present for a lesson date.
     *
     * @param   int     $lessonId  Lesson id.
     * @param   string  $date      Date in Y-m-d.
     *
     * @return  int[]
     *
     * @since   1.3.20
     */
    public function getTeachedTeacherIds(int $lessonId, string $date): array
    {
        if ($lessonId <= 0 || $date === '') {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('teacher'))
            ->from($db->quoteName('#__balancirk_teached'))
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('date') . ' = ' . $db->quote($date))
            ->order($db->quoteName('teacher') . ' ASC');

        return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
    }

    /**
     * Method to save the teachers of the lesson
     *
     * @param	int		$id			Id of the lesson
     * @param	array	$teachers	An array of the teachers
     *
     * @return void
     */
    public function saveTeacher($id, $date, $teachers)
    {
        $teachers = is_array($teachers) ? $teachers : [];
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);

        // Delete all teachers for this lesson
        $query->delete($dbo->quoteName('#__balancirk_teached'))
            ->where($dbo->quoteName('lesson') . ' = ' . (int) $id)
            ->where($dbo->quoteName('date') . ' = ' . $dbo->quote($date));
        $dbo->setQuery($query);
        $dbo->execute();

        // Insert the new teachers
        foreach ($teachers as $teacher) {
            $teacherId = (int) $teacher;

            if ($teacherId <= 0) {
                continue;
            }

            $this->ensureTeacherAssigned((int) $id, $teacherId);

            $query->clear();
            $query->insert($dbo->quoteName('#__balancirk_teached'))
                ->columns($dbo->quoteName(['lesson', 'teacher', 'date']))
                ->values((int) $id . ', ' . $teacherId . ', ' . $dbo->quote($date));
            $dbo->setQuery($query);
            $dbo->execute();
        }
    }

    /**
     * Ensure a teacher is assigned to the lesson before storing a teached row.
     *
     * @param   int  $lessonId   Lesson id.
     * @param   int  $teacherId  Member id of the teacher.
     *
     * @return  void
     *
     * @since   1.3.20
     */
    private function ensureTeacherAssigned(int $lessonId, int $teacherId): void
    {
        if ($lessonId <= 0 || $teacherId <= 0) {
            return;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('member'))
            ->from($db->quoteName('#__balancirk_teachers'))
            ->where($db->quoteName('member') . ' = ' . $teacherId)
            ->where($db->quoteName('lesson') . ' = ' . $lessonId);

        if ($db->setQuery($query)->loadResult()) {
            return;
        }

        $insert = $db->getQuery(true)
            ->insert($db->quoteName('#__balancirk_teachers'))
            ->columns($db->quoteName(['member', 'lesson']))
            ->values($teacherId . ', ' . $lessonId);
        $db->setQuery($insert)->execute();
    }

    /**
     * Method to send mail to the parent
     *
     * @param	subject	Subject of the mail to be sent to the parent
     * @param	text	Text to be sent to the parent
     *
     * @since	1.2.5
     */
    protected function sendMail($lessonid, $subject, $text)
    {
        $mailAdresses = array();

        // Get the mailadresses of the parents
        $dbo = $this->getDatabase();
        $query = $dbo->getQuery(true);
        $query->select($dbo->quoteName('p.email'))
            ->from('#__balancirk_subscriptions', 's')
            ->join('INNER', $dbo->quoteName('#__balancirk_parents', 'p'), 'p.id = s.parent', 's.subscribed = 0')
            ->where($dbo->quoteName('s.lesson') . ' = ' . $dbo->quote($lessonid));
        $dbo->setQuery($query);
        $mailAdresses[] = $dbo->loadResult();

        // Get the mailadresses of the teachers
        $query->clear();
        $query->select($dbo->quoteName('m.email'))
            ->from($dbo->quoteName('#__balancirk_members', 'm'))
            ->join('INNER', $dbo->quoteName('#__balancirk_teachers', 't'), 't.member = m.id')
            ->where($dbo->quoteName('t.les') . ' = ' . $dbo->quote($lessonid));
        $dbo->setQuery($query);
        $mailAdresses[] = $dbo->loadResult();

        // Get the mailadresses of the students
        $query->clear();
        $query->select($dbo->quoteName('s.email'))
            ->from('#__balancirk_subscriptions', 'l')
            ->join('INNER', $dbo->quoteName('#__balancirk_students', 's'), 's.id = l.student', 's.subscribed = 0')
            ->where($dbo->quoteName('l.lesson') . ' = ' . $dbo->quote($this->getState('lesson.student')));
        $dbo->setQuery($query);
        $mailAdresses[] = $dbo->loadResult();

        // Send mail
        $mailer =
            Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
        $mailer->setSender('info@balancirk.be', 'Balancirk Lesgevers')
            ->addRecipient($mailAdresses)
            ->addCc('rudi@balancirk.be', 'kris@balancirk.be')
            ->setSubject('Balancirk - Les ' . $this->getState('lesson.name') . ':  ' . $subject)
            ->setBody($text)
            ->Send();
    }
}
