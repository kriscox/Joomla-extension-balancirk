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
    public function testGetAgeOnLessonStartDate(): void
    {
        $age = LessonAgeHelper::getAgeOnDate('2015-09-02', '2025-09-01');

        $this->assertSame(9, $age);
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

    public function testMatchesLessonReturnsFalseBelowMinimumAge(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 10,
            'max_age' => 12,
        ];

        $this->assertFalse(LessonAgeHelper::matchesLesson('2016-10-10', $lesson));
    }

    public function testMatchesLessonReturnsFalseAboveMaximumAge(): void
    {
        $lesson = (object) [
            'start' => '2025-09-01',
            'min_age' => 6,
            'max_age' => 8,
        ];

        $this->assertFalse(LessonAgeHelper::matchesLesson('2015-01-01', $lesson));
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

    public function testGetAgeOnDateReturnsNullForInvalidDates(): void
    {
        $this->assertNull(LessonAgeHelper::getAgeOnDate('not-a-date', '2025-09-01'));
        $this->assertNull(LessonAgeHelper::getAgeOnDate('2015-01-01', 'invalid-reference'));
    }

    public function testNormalizeNullableIntCastsAndHandlesEmptyValues(): void
    {
        $this->assertNull(LessonAgeHelper::normalizeNullableInt(null));
        $this->assertNull(LessonAgeHelper::normalizeNullableInt(''));
        $this->assertSame(0, LessonAgeHelper::normalizeNullableInt('0'));
        $this->assertSame(12, LessonAgeHelper::normalizeNullableInt('12'));
    }
}
