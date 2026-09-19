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
 * Promote waiting-list subscriptions into enrolled seats.
 *
 * @since  1.3.24
 */
class WaitlistPromotionHelper
{
    /**
     * How many waitlist rows may become enrolled.
     *
     * @param   int  $maxStudents  Lesson capacity.
     * @param   int  $enrolled     Current enrolled count (subscribed = 0).
     * @param   int  $waiting      Current waiting-list count (subscribed = 1).
     * @param   int  $limit        Maximum promotions requested.
     *
     * @return  int
     *
     * @since   1.3.24
     */
    public static function seatsToPromote(int $maxStudents, int $enrolled, int $waiting, int $limit): int
    {
        if ($maxStudents < 0 || $enrolled < 0 || $waiting < 0 || $limit < 0) {
            return 0;
        }

        $free = max(0, $maxStudents - $enrolled);

        return max(0, min($limit, $free, $waiting));
    }

    /**
     * Count subscriptions for a lesson and status.
     *
     * @param   object  $db         Database driver.
     * @param   int     $lessonId   Lesson id.
     * @param   int     $subscribed 0 = enrolled, 1 = waiting list.
     *
     * @return  int
     *
     * @since   1.3.24
     */
    public static function countByStatus(object $db, int $lessonId, int $subscribed): int
    {
        if ($lessonId <= 0) {
            return 0;
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = ' . (int) $subscribed);

        return (int) $db->setQuery($query)->loadResult();
    }

    /**
     * Promote the oldest waiting-list rows for a lesson, up to free seats and $limit.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   int     $limit     Maximum rows to promote.
     *
     * @return  object[]  Promoted subscription rows (id, student, lesson).
     *
     * @since   1.3.24
     */
    public static function promoteFromWaitingList(object $db, int $lessonId, int $limit = 1): array
    {
        if ($lessonId <= 0 || $limit <= 0) {
            return [];
        }

        $maxStudents = self::loadMaxStudents($db, $lessonId);

        if ($maxStudents === null) {
            return [];
        }

        $enrolled = self::countByStatus($db, $lessonId, 0);
        $waiting = self::countByStatus($db, $lessonId, 1);
        $toPromote = self::seatsToPromote($maxStudents, $enrolled, $waiting, $limit);

        if ($toPromote === 0) {
            return [];
        }

        $rows = self::loadOldestWaiting($db, $lessonId, $toPromote);

        if ($rows === []) {
            return [];
        }

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        self::markEnrolled($db, $ids);

        return $rows;
    }

    /**
     * Load lesson capacity.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  int|null
     *
     * @since   1.3.24
     */
    public static function loadMaxStudents(object $db, int $lessonId): ?int
    {
        if ($lessonId <= 0) {
            return null;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('max_students'))
            ->from($db->quoteName('#__balancirk_lessons'))
            ->where($db->quoteName('id') . ' = ' . $lessonId);
        $value = $db->setQuery($query)->loadResult();

        return $value === null || $value === false ? null : (int) $value;
    }

    /**
     * Load the oldest waiting-list rows for a lesson.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   int     $limit     Maximum rows.
     *
     * @return  object[]
     *
     * @since   1.3.24
     */
    private static function loadOldestWaiting(object $db, int $lessonId, int $limit): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'student', 'lesson']))
            ->from($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = 1')
            ->order($db->quoteName('id') . ' ASC');

        return $db->setQuery($query, 0, $limit)->loadObjectList() ?: [];
    }

    /**
     * Mark subscription ids as enrolled.
     *
     * @param   object  $db   Database driver.
     * @param   int[]   $ids  Subscription ids.
     *
     * @return  void
     *
     * @since   1.3.24
     */
    private static function markEnrolled(object $db, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__balancirk_subscriptions'))
            ->set($db->quoteName('subscribed') . ' = 0')
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
            ->where($db->quoteName('subscribed') . ' = 1');
        $db->setQuery($query)->execute();
    }
}
