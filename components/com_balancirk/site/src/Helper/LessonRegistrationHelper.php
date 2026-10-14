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
 * Lesson registration open/closed rules.
 *
 * @since  1.3.25
 */
class LessonRegistrationHelper
{
    /**
     * Cancelled lesson state value.
     *
     * @var    string
     * @since  1.3.25
     */
    public const STATE_CANCELLED = '-1';

    /**
     * Whether a lesson accepts new subscriptions (enrolled or waitlist).
     *
     * @param   object       $lesson  Lesson row (needs state, registration_closed, dates).
     * @param   string|null  $today   Override date Y-m-d (tests).
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function isOpenForSubscription(object $lesson, ?string $today = null): bool
    {
        if ((string) ($lesson->state ?? '') !== '1') {
            return false;
        }

        if ((int) ($lesson->registration_closed ?? 0) === 1) {
            return false;
        }

        $startRegistration = (string) ($lesson->start_registration ?? '');
        $endRegistration = (string) ($lesson->end_registration ?? '');

        if ($startRegistration === '' || $endRegistration === '') {
            return false;
        }

        $today = $today ?? date('Y-m-d');

        return $today >= $startRegistration && $today <= $endRegistration;
    }

    /**
     * Whether the lesson is marked cancelled for the year.
     *
     * @param   object  $lesson  Lesson row.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function isCancelled(object $lesson): bool
    {
        return (string) ($lesson->state ?? '') === self::STATE_CANCELLED;
    }

    /**
     * Whether the lesson should show as full on the website.
     *
     * @param   object  $lesson  Lesson row (registration_closed, numberOfStudents, max_students).
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function isFull(object $lesson): bool
    {
        if ((int) ($lesson->registration_closed ?? 0) === 1) {
            return true;
        }

        $max = (int) ($lesson->max_students ?? 0);
        $enrolled = (int) ($lesson->numberOfStudents ?? 0);

        return $max > 0 && $enrolled >= $max;
    }

    /**
     * Close registrations for a lesson.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function closeRegistration(object $db, int $lessonId): bool
    {
        return self::setRegistrationClosed($db, $lessonId, true);
    }

    /**
     * Reopen registrations for a lesson.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    public static function reopenRegistration(object $db, int $lessonId): bool
    {
        return self::setRegistrationClosed($db, $lessonId, false);
    }

    /**
     * Set registration_closed flag.
     *
     * @param   object  $db        Database driver.
     * @param   int     $lessonId  Lesson id.
     * @param   bool    $closed    Closed flag.
     *
     * @return  bool
     *
     * @since   1.3.25
     */
    private static function setRegistrationClosed(object $db, int $lessonId, bool $closed): bool
    {
        if ($lessonId <= 0) {
            return false;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__balancirk_lessons'))
            ->set($db->quoteName('registration_closed') . ' = ' . ($closed ? 1 : 0))
            ->where($db->quoteName('id') . ' = ' . $lessonId);
        $db->setQuery($query)->execute();

        return true;
    }
}
