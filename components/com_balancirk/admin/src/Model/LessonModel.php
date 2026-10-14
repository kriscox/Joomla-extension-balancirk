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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Jooma\CMS\CMSApplicationInterface;
use Joomla\CMS\Application\CMSApplication;
use CoCoCo\Component\Balancirk\Site\Helper\LesdaysHelper;
use CoCoCo\Component\Balancirk\Site\Helper\WaitlistPromotionHelper;
use CoCoCo\Component\Balancirk\Site\Helper\WaitlistSettlementHelper;
use CoCoCo\Component\Balancirk\Site\Helper\LessonRegistrationHelper;
use CoCoCo\Component\Balancirk\Site\Helper\LessonCancellationHelper;
use CoCoCo\Component\Balancirk\Site\Helper\SubscriptionMailHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;

/**
 * Item model for lesson.
 *
 * @since  0.0.1
 */
class LessonModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  0.0.1
     */
    public $typeAlias = 'com_balancirk.lesson';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  0.0.1
     */
    protected $text_prefix = 'COM_BALANCIRK';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canDelete($record)
    {
        if (!empty($record->id)) {
            $app = Factory::getApplication();

            return $app->getIdentity()->authorise('core.delete');
        }

        return false;
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canEditState($record)
    {
        $user = Factory::getApplication()->getIdentity();

        // Check for existing article.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state');
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
     * @since   0.0.1
     * @throws  \Exception
     */
    public function getTable($name = '', $prefix = '', $options = array())
    {
        $name = 'lessons';
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
     * Validate lesson data.
     *
     * @param   Form    $form   The form to validate against.
     * @param   array   $data   The data to validate.
     * @param   string  $group  The name of the field group to validate.
     *
     * @return  array|bool
     *
     * @since   1.2.12
     */
    public function validate($form, $data, $group = null)
    {
        $validData = parent::validate($form, $data, $group);

        if ($validData === false) {
            return false;
        }

        $minAge = $validData['min_age'] ?? null;
        $maxAge = $validData['max_age'] ?? null;

        if ($minAge !== '' && $maxAge !== '' && $minAge !== null && $maxAge !== null && (int) $maxAge < (int) $minAge) {
            $this->setError(Text::_('COM_BALANCIRK_LESSON_AGE_RANGE_INVALID'));

            return false;
        }

        $start = (string) ($validData['start'] ?? '');
        $end = (string) ($validData['end'] ?? '');

        if ($start !== '' && $end !== '' && $start > $end) {
            $this->setError(Text::_('COM_BALANCIRK_LESSON_DATES_INVALID'));

            return false;
        }

        $startRegistration = (string) ($validData['start_registration'] ?? '');
        $endRegistration = (string) ($validData['end_registration'] ?? '');

        if ($startRegistration !== '' && $endRegistration !== '' && $startRegistration > $endRegistration) {
            $this->setError(Text::_('COM_BALANCIRK_LESSON_REGISTRATION_DATES_INVALID'));

            return false;
        }

        return $validData;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   0.0.1
     */
    protected function loadFormData()
    {
        /** @var CMSApplication $app*/
        $app = Factory::getApplication();
        $data = $app->getUserState('com_balancirk.edit.lesson.data', array());

        if (empty($data)) {
            $data = $this->getItem();

            if (is_object($data)) {
                $data->lesdays_field = LesdaysHelper::toFormValues((int) ($data->lesdays ?? 0));
            }
        }

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    /**
     * Load individual lessons with date and hour
     *
     * @param   int $lesson is the id of the lesson
     *
     * @return  array of hours
     *
     * @since   0.0.1
     */
    public function getHours(?int $lesson = null)
    {
        // Don't know if it works
        $lesson = (!is_null($lesson) ? $lesson : (int) $this->getState('lesson.id'));

        // Get the database connection
        $db = $this->getDatabase();

        $query = $db->getQuery(true);

        // Get all hours for the current lesson
        $query->select($db->quoteName(array('id', 'day')))
            ->from($db->quoteName('#__balancirk_hours'))
            ->where($db->quoteName('lesson') . ' = ' . $lesson);

        return $db->loadRowList();
    }

    /**
     * Selected lesson-day bit values for the checkboxes field.
     *
     * @param   int  $lesdays  Number representing days of lesson.
     *
     * @return  string[]  Bit values such as ["64", "4"], never a comma-separated string.
     **/
    public static function getLesdays($lesdays)
    {
        return LesdaysHelper::toFormValues((int) $lesdays);
    }

    /**
     * Get teachers assigned to a lesson.
     *
     * @param   int|null  $lessonId  Lesson id (defaults to current item).
     *
     * @return  array
     *
     * @since   1.3.2
     */
    public function getTeachers(?int $lessonId = null): array
    {
        $lessonId = $lessonId ?: (int) $this->getState('lesson.id');
        if (!$lessonId) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['m.id', 'm.firstname', 'u.name', 'u.email']))
            ->from($db->quoteName('#__balancirk_teachers', 't'))
            ->join(
                'INNER',
                $db->quoteName('#__balancirk_members_additional', 'm'),
                't.member = m.id'
            )
            ->join(
                'INNER',
                $db->quoteName('#__users', 'u'),
                'u.id = m.id'
            )
            ->where($db->quoteName('t.lesson') . ' = ' . (int) $lessonId)
            ->order('u.name');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Get members available as teachers (in the Teachers user group).
     *
     * @return  array
     *
     * @since   1.3.2
     */
    public function getAvailableTeachers(): array
    {
        $db = $this->getDatabase();

        $groupQuery = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = ' . $db->quote('Teachers'));
        $groupId = $db->setQuery($groupQuery)->loadResult();

        if (!$groupId) {
            return [];
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName(['m.id', 'm.firstname', 'u.name', 'u.email']))
            ->from($db->quoteName('#__balancirk_members_additional', 'm'))
            ->join(
                'INNER',
                $db->quoteName('#__users', 'u'),
                'u.id = m.id'
            )
            ->join(
                'INNER',
                $db->quoteName('#__user_usergroup_map', 'g'),
                'g.user_id = m.id'
            )
            ->where($db->quoteName('g.group_id') . ' = ' . (int) $groupId)
            ->order('u.name');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Get member ids assigned as teachers for a lesson.
     *
     * @param   int  $lessonId  Lesson id.
     *
     * @return  int[]
     *
     * @since   1.3.18
     */
    public function getTeacherIdsForLesson(int $lessonId): array
    {
        if ($lessonId <= 0) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('member'))
            ->from($db->quoteName('#__balancirk_teachers'))
            ->where($db->quoteName('lesson') . ' = ' . (int) $lessonId);

        return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
    }

    /**
     * Check whether a member has attendance records as a teacher for a lesson.
     *
     * FK fk_teached_teacher is composite (teacher, lesson) → teachers(member, lesson),
     * so only teached rows for this lesson block unassigning from that lesson.
     *
     * @param   int       $memberId  Member id.
     * @param   int|null  $lessonId  Lesson id (required for the composite FK check).
     *
     * @return  bool
     *
     * @since   1.3.18
     */
    public function hasTeachedRecords(int $memberId, ?int $lessonId = null): bool
    {
        return $this->countTeachedRecords($memberId, $lessonId) > 0;
    }

    /**
     * Count attendance records for a teacher member.
     *
     * When $lessonId is set, only rows for that lesson are counted (matches composite FK).
     * When null, all lessons are counted (legacy/global).
     *
     * @param   int       $memberId  Member id.
     * @param   int|null  $lessonId  Optional lesson id.
     *
     * @return  int
     *
     * @since   1.3.18
     */
    public function countTeachedRecords(int $memberId, ?int $lessonId = null): int
    {
        if ($memberId <= 0) {
            return 0;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__balancirk_teached'))
            ->where($db->quoteName('teacher') . ' = ' . (int) $memberId);

        if ($lessonId !== null && $lessonId > 0) {
            $query->where($db->quoteName('lesson') . ' = ' . (int) $lessonId);
        }

        return (int) $db->setQuery($query)->loadResult();
    }

    /**
     * Save teacher assignments for a lesson.
     *
     * @param   int    $lessonId    Lesson id.
     * @param   array  $teacherIds  Array of member ids to assign.
     *
     * @return  bool  True on success.
     *
     * @since   1.3.2
     */
    public function saveTeachers(int $lessonId, array $teacherIds): bool
    {
        $teacherIds = $this->normalizeTeacherIds($teacherIds);
        $current = $this->getTeacherIdsForLesson($lessonId);
        $toAdd = array_diff($teacherIds, $current);
        $toRemove = array_diff($current, $teacherIds);

        foreach ($toRemove as $memberId) {
            if (!$this->assertTeacherCanBeUnassigned((int) $memberId, $lessonId)) {
                return false;
            }
        }

        $db = $this->getDatabase();

        foreach ($toAdd as $memberId) {
            $insertQuery = $db->getQuery(true)
                ->insert($db->quoteName('#__balancirk_teachers'))
                ->columns([$db->quoteName('member'), $db->quoteName('lesson')])
                ->values((int) $memberId . ', ' . (int) $lessonId);
            $db->setQuery($insertQuery)->execute();
        }

        foreach ($toRemove as $memberId) {
            $deleteQuery = $db->getQuery(true)
                ->delete($db->quoteName('#__balancirk_teachers'))
                ->where($db->quoteName('lesson') . ' = ' . (int) $lessonId)
                ->where($db->quoteName('member') . ' = ' . (int) $memberId);
            $db->setQuery($deleteQuery)->execute();
        }

        return true;
    }

    /**
     * Override save to also handle teacher assignments.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   1.3.2
     */
    public function save($data)
    {
        $syncTeachers = \array_key_exists('teachers', $data);
        $teacherIds = $syncTeachers ? $this->normalizeTeacherIds((array) ($data['teachers'] ?? [])) : [];
        $promoteWaitlist = (int) ($data['promote_waitlist'] ?? 0);
        unset($data['teachers'], $data['teachers_sync'], $data['promote_waitlist']);

        $lessonId = (int) ($this->getState('lesson.id') ?: $data['id'] ?? 0);
        $previousMax = $this->getStoredMaxStudents($lessonId);
        $newMax = \array_key_exists('max_students', $data) ? (int) $data['max_students'] : $previousMax;

        if ($syncTeachers && $lessonId > 0 && !$this->canSyncTeachers($lessonId, $teacherIds)) {
            return false;
        }

        if (!$this->saveLessonRecord($data)) {
            return false;
        }

        $lessonId = (int) ($this->getState('lesson.id') ?: $data['id'] ?? 0);
        $this->handleCapacityChange($lessonId, $previousMax, $newMax, $promoteWaitlist);

        if ($syncTeachers && $lessonId > 0) {
            return $this->saveTeachers($lessonId, $teacherIds);
        }

        return true;
    }

    /**
     * Load the stored max_students value for a lesson.
     *
     * @param   int  $lessonId  Lesson id.
     *
     * @return  int|null
     *
     * @since   1.3.24
     */
    protected function getStoredMaxStudents(int $lessonId): ?int
    {
        if ($lessonId <= 0) {
            return null;
        }

        try {
            return WaitlistPromotionHelper::loadMaxStudents($this->getDatabase(), $lessonId);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * Warn on capacity decrease or promote waitlist after a confirmed increase.
     *
     * @param   int       $lessonId         Lesson id.
     * @param   int|null  $previousMax      Stored max_students before save.
     * @param   int|null  $newMax           Posted max_students.
     * @param   int       $promoteWaitlist  1 to promote after a real increase.
     *
     * @return  void
     *
     * @since   1.3.24
     */
    protected function handleCapacityChange(int $lessonId, ?int $previousMax, ?int $newMax, int $promoteWaitlist): void
    {
        if ($lessonId <= 0 || $previousMax === null || $newMax === null) {
            return;
        }

        if ($newMax < $previousMax) {
            $this->warnIfCapacityBelowEnrolled($lessonId, $newMax);

            return;
        }

        if ($newMax > $previousMax && $promoteWaitlist === 1) {
            $this->promoteWaitlistAfterCapacityIncrease($lessonId);
        }
    }

    /**
     * Warn when the new capacity is below the current enrolled count.
     *
     * @param   int  $lessonId  Lesson id.
     * @param   int  $newMax    New max_students.
     *
     * @return  void
     *
     * @since   1.3.24
     */
    protected function warnIfCapacityBelowEnrolled(int $lessonId, int $newMax): void
    {
        try {
            $enrolled = WaitlistPromotionHelper::countByStatus($this->getDatabase(), $lessonId, 0);
        } catch (\Throwable $exception) {
            return;
        }

        if ($enrolled > $newMax) {
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_BALANCIRK_LESSON_CAPACITY_BELOW_ENROLLED'),
                'warning'
            );
        }
    }

    /**
     * Promote waiting-list students after a confirmed capacity increase.
     *
     * @param   int  $lessonId  Lesson id.
     *
     * @return  int  Number of promoted students.
     *
     * @since   1.3.24
     */
    protected function promoteWaitlistAfterCapacityIncrease(int $lessonId): int
    {
        try {
            $model = $this->getMVCFactory()->createModel('Subscription', 'Administrator', ['ignore_request' => true]);

            if (!$model instanceof SubscriptionModel) {
                return 0;
            }

            $promoted = $model->promoteWaitingList($lessonId, PHP_INT_MAX);
        } catch (\Throwable $exception) {
            return 0;
        }

        $count = count($promoted);

        if ($count > 0) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_BALANCIRK_WAITLIST_N_PROMOTED', $count),
                'success'
            );
        }

        return $count;
    }

    /**
     * Persist lesson record data.
     *
     * @param   array  $data  Lesson data without teacher assignments.
     *
     * @return  bool
     *
     * @since   1.3.18
     */
    protected function saveLessonRecord(array $data): bool
    {
        return parent::save($data);
    }

    /**
     * Validate that requested teacher removals are allowed.
     *
     * @param   int    $lessonId    Lesson id.
     * @param   array  $teacherIds  Requested teacher member ids.
     *
     * @return  bool
     *
     * @since   1.3.18
     */
    private function canSyncTeachers(int $lessonId, array $teacherIds): bool
    {
        $current = $this->getTeacherIdsForLesson($lessonId);
        $toRemove = array_diff($current, $teacherIds);

        foreach ($toRemove as $memberId) {
            if (!$this->assertTeacherCanBeUnassigned((int) $memberId, $lessonId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Block unassign when teached rows exist for this member on this lesson.
     *
     * @param   int  $memberId  Member id.
     * @param   int  $lessonId  Lesson id.
     *
     * @return  bool
     *
     * @since   1.3.18
     */
    private function assertTeacherCanBeUnassigned(int $memberId, int $lessonId): bool
    {
        $teachedCount = $this->countTeachedRecords($memberId, $lessonId);

        if ($teachedCount > 0) {
            $this->setError(
                Text::sprintf(
                    'COM_BALANCIRK_LESSON_TEACHER_CANNOT_UNASSIGN_HAS_TEACHED',
                    $memberId,
                    $lessonId,
                    $teachedCount
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Normalize teacher member ids from request data.
     *
     * @param   array  $teacherIds  Raw teacher ids.
     *
     * @return  int[]
     *
     * @since   1.3.18
     */
    private function normalizeTeacherIds(array $teacherIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $teacherIds),
            static fn(int $id): bool => $id > 0
        )));
        sort($ids);

        return $ids;
    }

    /**
     * Get enrolled students for a lesson.
     *
     * @param   int|null  $lessonId  Lesson id.
     *
     * @return  array
     *
     * @since   1.3.21
     */
    public function getStudents(?int $lessonId = null): array
    {
        return $this->getSubscribedStudents($lessonId, 0);
    }

    /**
     * Get waiting-list students for a lesson.
     *
     * @param   int|null  $lessonId  Lesson id.
     *
     * @return  array
     *
     * @since   1.3.21
     */
    public function getWaitingListStudents(?int $lessonId = null): array
    {
        return $this->getSubscribedStudents($lessonId, 1);
    }

    /**
     * Load students linked to a lesson for a subscription status.
     *
     * @param   int|null  $lessonId   Lesson id.
     * @param   int       $subscribed 0 = enrolled, 1 = waiting list.
     *
     * @return  array
     *
     * @since   1.3.21
     */
    private function getSubscribedStudents(?int $lessonId, int $subscribed): array
    {
        $lessonId = $lessonId ?: (int) $this->getState('lesson.id');

        if (!$lessonId) {
            $lessonId = (int) ($this->getItem()->id ?? 0);
        }

        if (!$lessonId) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(
                $db->quoteName(
                    [
                        'a.id',
                        'a.name',
                        'a.firstname',
                        'a.birthdate',
                        's.id',
                    ],
                    [
                        'id',
                        'name',
                        'firstname',
                        'birthdate',
                        'subscription_id',
                    ]
                )
            )
            ->from($db->quoteName('#__balancirk_students', 'a'))
            ->join(
                'INNER',
                $db->quoteName('#__balancirk_subscriptions', 's')
                . ' ON s.student = a.id AND s.subscribed = ' . (int) $subscribed
            )
            ->where($db->quoteName('s.lesson') . ' = ' . (int) $lessonId)
            ->order($db->quoteName('a.name') . ' ASC, ' . $db->quoteName('a.firstname') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Waiting list in FIFO order for year-start settlement UI/API.
     *
     * @param   int|null  $lessonId  Lesson id.
     *
     * @return  object[]
     *
     * @since   1.3.25
     */
    public function getWaitlistOrdered(?int $lessonId = null): array
    {
        $lessonId = $lessonId ?: (int) $this->getState('lesson.id');

        if (!$lessonId) {
            $lessonId = (int) ($this->getItem()->id ?? 0);
        }

        return WaitlistSettlementHelper::listWaitingOrdered($this->getDatabase(), $lessonId);
    }

    /**
     * Settle waitlist: promote and/or dismiss selected subscription ids.
     *
     * @param   int    $lessonId             Lesson id.
     * @param   int[]  $promoteIds           Subscription ids to enrol.
     * @param   int[]  $dismissIds           Subscription ids to reject/delete.
     * @param   bool   $confirmFifoOverride  Required when promote selection is not FIFO prefix.
     * @param   bool   $closeRegistration    Close registrations after settle.
     *
     * @return  array{promoted:int, dismissed:int, closed:bool}|false
     *
     * @since   1.3.25
     */
    public function settleWaitlist(
        int $lessonId,
        array $promoteIds,
        array $dismissIds,
        bool $confirmFifoOverride = false,
        bool $closeRegistration = true
    ) {
        if ($lessonId <= 0) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'));

            return false;
        }

        $db = $this->getDatabase();
        $waiting = WaitlistSettlementHelper::listWaitingOrdered($db, $lessonId);
        $orderedIds = [];

        foreach ($waiting as $row) {
            $orderedIds[] = (int) $row->id;
        }

        $promoteIds = array_values(array_unique(array_filter(array_map('intval', $promoteIds))));
        $dismissIds = array_values(array_unique(array_filter(array_map('intval', $dismissIds))));

        if (array_intersect($promoteIds, $dismissIds) !== []) {
            $this->setError(Text::_('COM_BALANCIRK_WAITLIST_SETTLE_OVERLAP'));

            return false;
        }

        if ($promoteIds !== [] && !WaitlistSettlementHelper::isFifoPrefix($promoteIds, $orderedIds) && !$confirmFifoOverride) {
            $this->setError(Text::_('COM_BALANCIRK_WAITLIST_SETTLE_FIFO_REQUIRED'));

            return false;
        }

        $lesson = $this->loadLessonRow($lessonId);

        if (!$lesson) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'));

            return false;
        }

        $promoted = WaitlistSettlementHelper::promoteByIds($db, $lessonId, $promoteIds);
        $dismissed = WaitlistSettlementHelper::dismissByIds($db, $lessonId, $dismissIds);

        $mailDefaults = $this->getMailDefaults();
        $today = date('Y-m-d');

        foreach ($promoted as $row) {
            $this->sendSettlementMails(
                $lesson,
                (int) $row->student,
                $today,
                $mailDefaults,
                'yearstart'
            );
        }

        foreach ($dismissed as $row) {
            $this->sendSettlementMails(
                $lesson,
                (int) $row->student,
                $today,
                $mailDefaults,
                'rejection'
            );
        }

        $closed = false;

        if ($closeRegistration) {
            $closed = LessonRegistrationHelper::closeRegistration($db, $lessonId);
        }

        return [
            'promoted' => count($promoted),
            'dismissed' => count($dismissed),
            'closed' => $closed,
        ];
    }

    /**
     * Close lesson registrations.
     *
     * @param   int  $lessonId  Lesson id.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public function closeRegistration(int $lessonId): bool
    {
        return LessonRegistrationHelper::closeRegistration($this->getDatabase(), $lessonId);
    }

    /**
     * Reopen lesson registrations.
     *
     * @param   int  $lessonId  Lesson id.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public function reopenRegistration(int $lessonId): bool
    {
        return LessonRegistrationHelper::reopenRegistration($this->getDatabase(), $lessonId);
    }

    /**
     * Build cancellation mail preview for enrolled students (and waiting list).
     *
     * @param   int  $lessonId  Lesson id.
     *
     * @return  array{lesson:object, enrolled:array, waiting:array}|false
     *
     * @since   1.3.25
     */
    public function getCancellationPreview(int $lessonId)
    {
        $lesson = $this->loadLessonRow($lessonId);

        if (!$lesson) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'));

            return false;
        }

        $db = $this->getDatabase();
        $mailDefaults = $this->getMailDefaults();
        $today = date('Y-m-d');

        $enrolled = [];

        foreach (LessonCancellationHelper::listEnrolledForCancellation($db, $lessonId) as $row) {
            $enrolled[] = $this->buildRecipientPreview($lesson, $row, $today, $mailDefaults, 'cancellation');
        }

        $waiting = [];

        foreach (LessonCancellationHelper::listWaitingForCancellation($db, $lessonId) as $row) {
            $waiting[] = $this->buildRecipientPreview($lesson, $row, $today, $mailDefaults, 'rejection');
        }

        return [
            'lesson' => $lesson,
            'enrolled' => $enrolled,
            'waiting' => $waiting,
        ];
    }

    /**
     * Cancel the lesson for the year, send personalized mails, remove enrolments and waitlist.
     *
     * @param   int    $lessonId          Lesson id.
     * @param   array  $enrolledMessages  List of {subscription_id, member_id, subject, body}.
     * @param   array  $waitingMessages   List of {subscription_id, member_id, subject, body} (optional dismiss).
     * @param   bool   $dismissWaiting    Whether to dismiss waiting list after mails.
     *
     * @return  array{cancelled:bool, mailed:int, unenrolled:int, dismissed:int}|false
     *
     * @since   1.3.25
     */
    public function cancelLessonWithMails(
        int $lessonId,
        array $enrolledMessages,
        array $waitingMessages = [],
        bool $dismissWaiting = true
    ) {
        $lesson = $this->loadLessonRow($lessonId);

        if (!$lesson) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'));

            return false;
        }

        $db = $this->getDatabase();
        LessonCancellationHelper::cancelLesson($db, $lessonId);

        $mailed = 0;
        $studentBySubscription = [];
        $enrolledRows = LessonCancellationHelper::listEnrolledForCancellation($db, $lessonId);
        $enrolledIds = [];

        foreach ($enrolledRows as $row) {
            $studentBySubscription[(int) $row->subscription_id] = (int) $row->student;
            $enrolledIds[] = (int) $row->subscription_id;
        }

        $coveredEnrolled = [];

        foreach ($enrolledMessages as $message) {
            $subscriptionId = (int) ($message['subscription_id'] ?? 0);
            $memberId = (int) ($message['member_id'] ?? 0);
            $subject = (string) ($message['subject'] ?? '');
            $body = (string) ($message['body'] ?? '');
            $studentId = $studentBySubscription[$subscriptionId] ?? 0;

            if ($studentId <= 0 || $subject === '' || $body === '') {
                continue;
            }

            $coveredEnrolled[$subscriptionId] = true;

            if ($this->sendCustomMailToMember($lesson, $studentId, $memberId, $subject, $body, 'cancellation')) {
                $mailed++;
            }
        }

        $mailDefaults = $this->getMailDefaults();
        $today = date('Y-m-d');

        foreach ($enrolledRows as $row) {
            if (isset($coveredEnrolled[(int) $row->subscription_id])) {
                continue;
            }

            $this->sendSettlementMails($lesson, (int) $row->student, $today, $mailDefaults, 'cancellation');
            $mailed++;
        }

        $unenrolledRows = LessonCancellationHelper::dismissEnrolledByIds($db, $lessonId, $enrolledIds);
        $unenrolled = count($unenrolledRows);

        $dismissed = 0;

        if ($dismissWaiting) {
            $waitingRows = LessonCancellationHelper::listWaitingForCancellation($db, $lessonId);
            $waitingIds = [];
            $coveredSubscriptions = [];

            foreach ($waitingRows as $row) {
                $waitingIds[] = (int) $row->subscription_id;
                $studentBySubscription[(int) $row->subscription_id] = (int) $row->student;
            }

            foreach ($waitingMessages as $message) {
                $subscriptionId = (int) ($message['subscription_id'] ?? 0);
                $memberId = (int) ($message['member_id'] ?? 0);
                $subject = (string) ($message['subject'] ?? '');
                $body = (string) ($message['body'] ?? '');
                $studentId = $studentBySubscription[$subscriptionId] ?? 0;

                if ($studentId <= 0 || $subject === '' || $body === '') {
                    continue;
                }

                $coveredSubscriptions[$subscriptionId] = true;

                if ($this->sendCustomMailToMember($lesson, $studentId, $memberId, $subject, $body, 'rejection')) {
                    $mailed++;
                }
            }

            foreach ($waitingRows as $row) {
                $subscriptionId = (int) $row->subscription_id;

                if (isset($coveredSubscriptions[$subscriptionId])) {
                    continue;
                }

                $this->sendSettlementMails($lesson, (int) $row->student, $today, $mailDefaults, 'rejection');
                $mailed++;
            }

            $dismissedRows = WaitlistSettlementHelper::dismissByIds($db, $lessonId, $waitingIds);
            $dismissed = count($dismissedRows);
        }

        return [
            'cancelled' => true,
            'mailed' => $mailed,
            'unenrolled' => $unenrolled,
            'dismissed' => $dismissed,
        ];
    }

    /**
     * @param   int  $lessonId  Lesson id.
     *
     * @return  object|null
     *
     * @since   1.3.25
     */
    private function loadLessonRow(int $lessonId): ?object
    {
        if ($lessonId <= 0) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__balancirk_lessons'))
            ->where($db->quoteName('id') . ' = ' . $lessonId);
        $lesson = $db->setQuery($query)->loadObject();

        return $lesson ?: null;
    }

    /**
     * @return  array<string, string>
     *
     * @since   1.3.25
     */
    private function getMailDefaults(): array
    {
        $params = ComponentHelper::getParams('com_balancirk');

        return [
            'yearstart_subject' => (string) $params->get('email_subject_yearstart', ''),
            'yearstart_body' => (string) $params->get('email_body_yearstart', ''),
            'rejection_subject' => (string) $params->get('email_subject_rejection', ''),
            'rejection_body' => (string) $params->get('email_body_rejection', ''),
            'cancellation_subject' => (string) $params->get('email_subject_cancellation', ''),
            'cancellation_body' => (string) $params->get('email_body_cancellation', ''),
        ];
    }

    /**
     * @param   object  $lesson        Lesson.
     * @param   object  $row           Subscription/student row.
     * @param   string  $today         Date Y-m-d.
     * @param   array   $mailDefaults  Defaults.
     * @param   string  $type          cancellation|rejection.
     *
     * @return  array
     *
     * @since   1.3.25
     */
    private function buildRecipientPreview(
        object $lesson,
        object $row,
        string $today,
        array $mailDefaults,
        string $type
    ): array {
        $student = (object) [
            'firstname' => $row->firstname ?? '',
            'name' => $row->name ?? '',
        ];
        $recipients = [];

        foreach (LessonCancellationHelper::loadParentMembers($this->getDatabase(), (int) $row->student) as $member) {
            if ($type === 'rejection') {
                $message = SubscriptionMailHelper::buildRejectionMailMessage(
                    $lesson,
                    $student,
                    $member,
                    $today,
                    $mailDefaults
                );
            } else {
                $message = SubscriptionMailHelper::buildCancellationMailMessage(
                    $lesson,
                    $student,
                    $member,
                    $today,
                    $mailDefaults
                );
            }

            $recipients[] = [
                'member_id' => (int) $member->id,
                'member_name' => trim(($member->firstname ?? '') . ' ' . ($member->name ?? '')),
                'email' => (string) ($member->email ?? ''),
                'subject' => $message['subject'],
                'body' => $message['body'],
            ];
        }

        return [
            'subscription_id' => (int) $row->subscription_id,
            'student_id' => (int) $row->student,
            'student_name' => trim(($row->firstname ?? '') . ' ' . ($row->name ?? '')),
            'recipients' => $recipients,
        ];
    }

    /**
     * @param   object  $lesson        Lesson.
     * @param   int     $studentId     Student id.
     * @param   string  $today         Date.
     * @param   array   $mailDefaults  Defaults.
     * @param   string  $type          yearstart|rejection.
     *
     * @return  void
     *
     * @since   1.3.25
     */
    private function sendSettlementMails(
        object $lesson,
        int $studentId,
        string $today,
        array $mailDefaults,
        string $type
    ): void {
        $student = $this->loadStudentRow($studentId);

        if (!$student) {
            return;
        }

        foreach (LessonCancellationHelper::loadParentMembers($this->getDatabase(), $studentId) as $member) {
            if (empty($member->email)) {
                continue;
            }

            try {
                if ($type === 'rejection') {
                    $message = SubscriptionMailHelper::buildRejectionMailMessage(
                        $lesson,
                        $student,
                        $member,
                        $today,
                        $mailDefaults
                    );
                } elseif ($type === 'cancellation') {
                    $message = SubscriptionMailHelper::buildCancellationMailMessage(
                        $lesson,
                        $student,
                        $member,
                        $today,
                        $mailDefaults
                    );
                } else {
                    $message = SubscriptionMailHelper::buildYearstartPromotionMailMessage(
                        $lesson,
                        $student,
                        $member,
                        $today,
                        $mailDefaults
                    );
                }

                $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
                $mailer->setSender('info@balancirk.be', 'Circusatelier Balancirk VZW')
                    ->addRecipient($member->email)
                    ->setSubject($message['subject'])
                    ->setBody($message['body'])
                    ->Send();
            } catch (\Throwable $exception) {
                // Mail failure must not roll back settlement.
            }
        }
    }

    /**
     * Send a personalized mail; subject/body are used as templates (placeholders applied).
     *
     * @param   object  $lesson     Lesson.
     * @param   int     $studentId  Student id.
     * @param   int     $memberId   Parent member id (0 = all parents).
     * @param   string  $subject    Subject template or final text.
     * @param   string  $body       Body template or final text.
     * @param   string  $type       cancellation|rejection.
     *
     * @return  bool  True if at least one mail was attempted.
     *
     * @since   1.3.25
     */
    private function sendCustomMailToMember(
        object $lesson,
        int $studentId,
        int $memberId,
        string $subject,
        string $body,
        string $type
    ): bool {
        $student = $this->loadStudentRow($studentId);

        if (!$student) {
            return false;
        }

        $sent = false;
        $today = date('Y-m-d');

        foreach (LessonCancellationHelper::loadParentMembers($this->getDatabase(), $studentId) as $member) {
            if ($memberId > 0 && (int) $member->id !== $memberId) {
                continue;
            }

            if (empty($member->email)) {
                continue;
            }

            try {
                if ($type === 'rejection') {
                    $message = SubscriptionMailHelper::buildRejectionMailMessage(
                        $lesson,
                        $student,
                        $member,
                        $today,
                        [
                            'rejection_subject' => $subject,
                            'rejection_body' => $body,
                        ]
                    );
                    // Prefer exact personalized text when provided as already rendered.
                    $message = [
                        'subject' => SubscriptionMailHelper::renderTemplate(
                            $subject,
                            SubscriptionMailHelper::buildContext($lesson, $student, $member, $today, true)
                        ),
                        'body' => SubscriptionMailHelper::renderTemplate(
                            $body,
                            SubscriptionMailHelper::buildContext($lesson, $student, $member, $today, true)
                        ),
                    ];
                } else {
                    $message = [
                        'subject' => SubscriptionMailHelper::renderTemplate(
                            $subject,
                            SubscriptionMailHelper::buildContext($lesson, $student, $member, $today, false)
                        ),
                        'body' => SubscriptionMailHelper::renderTemplate(
                            $body,
                            SubscriptionMailHelper::buildContext($lesson, $student, $member, $today, false)
                        ),
                    ];
                }

                $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
                $mailer->setSender('info@balancirk.be', 'Circusatelier Balancirk VZW')
                    ->addRecipient($member->email)
                    ->setSubject($message['subject'])
                    ->setBody($message['body'])
                    ->Send();
                $sent = true;
            } catch (\Throwable $exception) {
                // Continue other recipients.
            }
        }

        return $sent;
    }

    /**
     * @param   int  $studentId  Student id.
     *
     * @return  object|null
     *
     * @since   1.3.25
     */
    private function loadStudentRow(int $studentId): ?object
    {
        if ($studentId <= 0) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__balancirk_students'))
            ->where($db->quoteName('id') . ' = ' . $studentId);
        $student = $db->setQuery($query)->loadObject();

        return $student ?: null;
    }
}
