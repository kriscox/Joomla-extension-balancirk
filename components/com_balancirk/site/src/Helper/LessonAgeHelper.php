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

use DateTimeImmutable;
use Throwable;

/**
 * Helper methods for lesson age restrictions.
 *
 * @since  1.2.12
 */
class LessonAgeHelper
{
    /**
     * Check whether a student matches the lesson age category.
     *
     * @param   string|null  $birthdate  Student birthdate.
     * @param   object       $lesson     Lesson record.
     *
     * @return  bool
     *
     * @since   1.2.12
     */
    public static function matchesLesson(?string $birthdate, object $lesson): bool
    {
        $minAge = self::normalizeNullableInt($lesson->min_age ?? null);
        $maxAge = self::normalizeNullableInt($lesson->max_age ?? null);

        if ($minAge === null && $maxAge === null) {
            return true;
        }

        $referenceDate = (string) ($lesson->start ?? '');
        $age = self::getAgeInYear($birthdate, $referenceDate);

        if ($age === null) {
            return false;
        }

        if ($minAge !== null && $age < $minAge) {
            return false;
        }

        if ($maxAge !== null && $age > $maxAge) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the age a student reaches during the reference calendar year.
     *
     * The age restriction is year based (not date based): everyone who turns
     * the configured age during the calendar year of the lesson qualifies.
     * This matches the way school grades ("leerjaren") are grouped.
     *
     * @param   string|null  $birthdate      Student birthdate.
     * @param   string|null  $referenceDate  Reference date.
     *
     * @return  int|null
     *
     * @since   1.3.6
     */
    public static function getAgeInYear(?string $birthdate, ?string $referenceDate): ?int
    {
        if (empty($birthdate)) {
            return null;
        }

        try {
            $birthDateObject = new DateTimeImmutable($birthdate);
            $referenceDateObject = new DateTimeImmutable($referenceDate ?: 'now');
        } catch (Throwable $exception) {
            return null;
        }

        return (int) $referenceDateObject->format('Y') - (int) $birthDateObject->format('Y');
    }

    /**
     * Convert a lesson age value to an integer or null.
     *
     * @param   mixed  $value  Input value.
     *
     * @return  int|null
     *
     * @since   1.2.12
     */
    public static function normalizeNullableInt($value): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return (int) $value;
    }
}
