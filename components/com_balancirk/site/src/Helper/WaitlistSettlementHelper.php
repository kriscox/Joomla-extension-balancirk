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
 * Manual year-start waitlist settlement (promote / dismiss by id).
 *
 * @since  1.3.25
 */
class WaitlistSettlementHelper
{
    /**
     * Load waiting-list rows in FIFO order (subscription id ASC) with ranks.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  object[]  Rows with id, student, lesson, firstname, name, birthdate, fifo_rank.
     *
     * @since   1.3.25
     */
    public static function listWaitingOrdered(object $db, int $lessonId): array
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
                        's.lesson',
                        'a.firstname',
                        'a.name',
                        'a.birthdate',
                    ],
                    [
                        'id',
                        'student',
                        'lesson',
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
            ->where($db->quoteName('s.subscribed') . ' = 1')
            ->order($db->quoteName('s.id') . ' ASC');

        $rows = $db->setQuery($query)->loadObjectList() ?: [];
        $rank = 1;

        foreach ($rows as $row) {
            $row->fifo_rank = $rank++;
            $row->subscription_id = (int) $row->id;
        }

        return $rows;
    }

    /**
     * Whether promote ids are exactly the first N FIFO waiting-list ids (any order of those N).
     *
     * @param   int[]  $selectedIds  Selected subscription ids to promote.
     * @param   int[]  $orderedIds   Full FIFO-ordered waiting-list subscription ids.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function isFifoPrefix(array $selectedIds, array $orderedIds): bool
    {
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds), static fn(int $id): bool => $id > 0)));
        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds), static fn(int $id): bool => $id > 0));

        if ($selectedIds === []) {
            return true;
        }

        $count = count($selectedIds);

        if ($count > count($orderedIds)) {
            return false;
        }

        $prefix = array_slice($orderedIds, 0, $count);
        sort($selectedIds);
        $prefixSorted = $prefix;
        sort($prefixSorted);

        return $selectedIds === $prefixSorted;
    }

    /**
     * Promote waiting-list subscription ids to enrolled (ignores max_students).
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   int[]   $ids       Subscription ids (must be waitlist on this lesson).
     *
     * @return  object[]  Promoted rows (id, student, lesson).
     *
     * @since   1.3.25
     */
    public static function promoteByIds(object $db, int $lessonId, array $ids): array
    {
        $ids = self::normalizeIds($ids);

        if ($lessonId <= 0 || $ids === []) {
            return [];
        }

        $rows = self::loadWaitingByIds($db, $lessonId, $ids);

        if ($rows === []) {
            return [];
        }

        $promotedIds = [];

        foreach ($rows as $row) {
            $promotedIds[] = (int) $row->id;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__balancirk_subscriptions'))
            ->set($db->quoteName('subscribed') . ' = 0')
            ->where($db->quoteName('id') . ' IN (' . implode(',', $promotedIds) . ')')
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = 1');
        $db->setQuery($query)->execute();

        return $rows;
    }

    /**
     * Delete waiting-list subscription ids and return the removed rows.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   int[]   $ids       Subscription ids.
     *
     * @return  object[]  Dismissed rows (id, student, lesson).
     *
     * @since   1.3.25
     */
    public static function dismissByIds(object $db, int $lessonId, array $ids): array
    {
        $ids = self::normalizeIds($ids);

        if ($lessonId <= 0 || $ids === []) {
            return [];
        }

        $rows = self::loadWaitingByIds($db, $lessonId, $ids);

        if ($rows === []) {
            return [];
        }

        $dismissedIds = [];

        foreach ($rows as $row) {
            $dismissedIds[] = (int) $row->id;
        }

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $dismissedIds) . ')')
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = 1');
        $db->setQuery($query)->execute();

        return $rows;
    }

    /**
     * @param   int[]  $ids  Raw ids.
     *
     * @return  int[]
     *
     * @since   1.3.25
     */
    private static function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
    }

    /**
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   int[]   $ids       Subscription ids.
     *
     * @return  object[]
     *
     * @since   1.3.25
     */
    private static function loadWaitingByIds(object $db, int $lessonId, array $ids): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'student', 'lesson']))
            ->from($db->quoteName('#__balancirk_subscriptions'))
            ->where($db->quoteName('lesson') . ' = ' . $lessonId)
            ->where($db->quoteName('subscribed') . ' = 1')
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
            ->order($db->quoteName('id') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }
}
