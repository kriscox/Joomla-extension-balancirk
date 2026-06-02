<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Helper;

use PHPUnit\Framework\TestCase;
use CoCoCo\Component\Balancirk\Site\Helper\LessonAgeHelper;

/**
 * Test class for lesson age restrictions.
 *
 * @since  1.2.12
 */
class LessonAgeHelperTest extends TestCase
{
    public function testGetAgeInYearUsesCalendarYearDifference(): void
    {
        // Turns 10 during 2025 even though the birthday falls after the lesson start.
        $age = LessonAgeHelper::getAgeInYear('2015-09-02', '2025-09-01');

        $this->assertSame(10, $age);
    }

    public function testMatchesLessonReturnsTrueWithinConfiguredRange(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 8,
            'max_age' => 10,
        ];

        $this->assertTrue(LessonAgeHelper::matchesLesson('2015-08-20', $lesson));
    }

    public function testMatchesLessonIncludesStudentReachingMinimumAgeLaterInYear(): void
    {
        // Born late in 2016, turns 9 during 2025: should qualify for a 9+ lesson.
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 9,
            'max_age' => 11,
        ];

        $this->assertTrue(LessonAgeHelper::matchesLesson('2016-10-10', $lesson));
    }

    public function testMatchesLessonReturnsFalseBelowMinimumAge(): void
    {
        // Born in 2017, turns only 8 during 2025: below the 9+ minimum.
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 9,
            'max_age' => 11,
        ];

        $this->assertFalse(LessonAgeHelper::matchesLesson('2017-01-05', $lesson));
    }

    public function testMatchesLessonReturnsFalseAboveMaximumAge(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 6,
            'max_age' => 8,
        ];

        $this->assertFalse(LessonAgeHelper::matchesLesson('2015-12-31', $lesson));
    }

    public function testMatchesLessonReturnsTrueWithoutAgeRestrictions(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => null,
            'max_age' => null,
        ];

        $this->assertTrue(LessonAgeHelper::matchesLesson('2010-01-01', $lesson));
    }

    public function testMatchesLessonReturnsFalseWhenBirthdateMissingAndRangeConfigured(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 8,
            'max_age' => 10,
        ];

        $this->assertFalse(LessonAgeHelper::matchesLesson(null, $lesson));
    }

    public function testGetAgeInYearReturnsNullForInvalidDates(): void
    {
        $this->assertNull(LessonAgeHelper::getAgeInYear('not-a-date', '2025-09-01'));
        $this->assertNull(LessonAgeHelper::getAgeInYear('2015-01-01', 'invalid-reference'));
    }

    public function testNormalizeNullableIntCastsAndHandlesEmptyValues(): void
    {
        $this->assertNull(LessonAgeHelper::normalizeNullableInt(null));
        $this->assertNull(LessonAgeHelper::normalizeNullableInt(''));
        $this->assertSame(0, LessonAgeHelper::normalizeNullableInt('0'));
        $this->assertSame(12, LessonAgeHelper::normalizeNullableInt('12'));
    }

    public function testMatchesLessonWithOnlyMinAgeAllowsStudentOldEnough(): void
    {
        // max_age is null: any student turning 9+ during the year qualifies.
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 9,
            'max_age' => null,
        ];

        $this->assertTrue(LessonAgeHelper::matchesLesson('2013-03-15', $lesson));
    }

    public function testMatchesLessonWithOnlyMinAgeBlocksStudentTooYoung(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 9,
            'max_age' => null,
        ];

        // Born in 2018, turns only 7 during 2025: below minimum.
        $this->assertFalse(LessonAgeHelper::matchesLesson('2018-06-01', $lesson));
    }

    public function testMatchesLessonWithOnlyMaxAgeAllowsStudentYoungEnough(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => null,
            'max_age' => 12,
        ];

        // Born in 2018, turns 7 during 2025: below or equal to max of 12.
        $this->assertTrue(LessonAgeHelper::matchesLesson('2018-03-15', $lesson));
    }

    public function testMatchesLessonWithOnlyMaxAgeBlocksStudentTooOld(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => null,
            'max_age' => 12,
        ];

        // Born in 2010, turns 15 during 2025: exceeds maximum.
        $this->assertFalse(LessonAgeHelper::matchesLesson('2010-01-01', $lesson));
    }

    public function testGetAgeInYearReturnsZeroWhenBornInReferenceYear(): void
    {
        $age = LessonAgeHelper::getAgeInYear('2025-06-15', '2025-09-01');

        $this->assertSame(0, $age);
    }

    public function testGetAgeInYearReturnsNullForEmptyBirthdate(): void
    {
        $this->assertNull(LessonAgeHelper::getAgeInYear('', '2025-09-01'));
        $this->assertNull(LessonAgeHelper::getAgeInYear(null, '2025-09-01'));
    }
}
