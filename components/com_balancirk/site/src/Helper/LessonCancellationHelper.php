<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Helper;

\defined('_JEXEC') or die;

/**
 * Cancel an entire lesson for the school year.
 *
 * @since  1.3.25
 */
class LessonCancellationHelper
{
    /**
     * Mark a lesson as cancelled and close registration.
     *
     * Subscriptions are removed separately after notification mails are sent.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function cancelLesson(object $db, int $lessonId): bool
    {
        if ($lessonId <= 0) {
            return false;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__balancirk_lessons'))
            ->set($db->quoteName('state') . ' = ' . $db->quote(LessonRegistrationHelper::STATE_CANCELLED))
            ->set($db->quoteName('registration_closed') . ' = 1')
            ->where($db->quoteName('id') . ' = ' . $lessonId);
        $db->setQuery($query)->execute();

        return true;
    }

    /**
     * Delete enrolled subscriptions for a lesson by subscription id.
     *
     * Does not trigger waitlist auto-promotion.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   int[]   $ids       Subscription ids.
     *
     * @return  object[]  Removed rows (id, student, lesson).
     *
     * @since   1.3.25
     */
    public static function dismissEnrolledByIds(object $db, int $lessonId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));

        if ($lessonId <= 0 || $ids === []) {
            return [];
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'student', 'lesson']))
            ->from($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = 0')
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        $rows = $db->setQuery($query)->loadObjectList() ?: [];

        if ($rows === []) {
            return [];
        }

        $removedIds = [];

        foreach ($rows as $row) {
            $removedIds[] = (int) $row->id;
        }

        $delete = $db->getQuery(true)
            ->delete($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $removedIds) . ')')
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = 0');
        $db->setQuery($delete)->execute();

        return $rows;
    }

    /**
     * Load enrolled subscriptions with student info for cancellation preview.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  object[]  Rows: subscription_id, student, firstname, name, birthdate.
     *
     * @since   1.3.25
     */
    public static function listEnrolledForCancellation(object $db, int $lessonId): array
    {
        return self::listSubscriptionsByStatus($db, $lessonId, 0);
    }

    /**
     * Load waiting-list subscriptions for optional rejection during cancel.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  object[]
     *
     * @since   1.3.25
     */
    public static function listWaitingForCancellation(object $db, int $lessonId): array
    {
        return self::listSubscriptionsByStatus($db, $lessonId, 1);
    }

    /**
     * Load parent members with email for a student.
     *
     * @param   object  $db         Database driver.
     * @param   int     $studentId  Student id.
     *
     * @return  object[]
     *
     * @since   1.3.25
     */
    public static function loadParentMembers(object $db, int $studentId): array
    {
        if ($studentId <= 0) {
            return [];
        }

        $query = $db->getQuery(true)
            ->select(
                $db->quoteName(
                    ['m.id', 'm.firstname', 'm.name', 'm.email'],
                    ['id', 'firstname', 'name', 'email']
                )
            )
            ->from($db->quoteName('#__balancirk_parents', 'p'))
            ->join(
                'INNER',
                $db->quoteName('#__balancirk_members', 'm')
                . ' ON ' . $db->quoteName('p.parent') . ' = ' . $db->quoteName('m.id')
            )
            ->where($db->quoteName('p.child') . ' = ' . $studentId);

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * @param   object  $db          Database driver.
     * @param   int     $lessonId    Lesson id.
     * @param   int     $subscribed  0 enrolled, 1 waitlist.
     *
     * @return  object[]
     *
     * @since   1.3.25
     */
    private static function listSubscriptionsByStatus(object $db, int $lessonId, int $subscribed): array
    {
        if ($lessonId <= 0) {
            return [];
        }

        $query = $db->getQuery(true)
            ->select(
                $db->quoteName(
                    [
                        's.id',
                        's.student',
                        'a.firstname',
                        'a.name',
                        'a.birthdate',
                    ],
                    [
                        'subscription_id',
                        'student',
                        'firstname',
                        'name',
                        'birthdate',
                    ]
                )
            )
            ->from($db->quoteName('#__balancirk_subscriptions', 's'))
            ->join(
                'INNER',
                $db->quoteName('#__balancirk_students', 'a') . ' ON a.id = s.student'
            )
            ->where($db->quoteName('s.lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('s.subscribed') . ' = ' . (int) $subscribed)
            ->order($db->quoteName('a.name') . ' ASC, ' . $db->quoteName('a.firstname') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }
}
