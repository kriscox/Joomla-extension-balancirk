<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Test class for lesson date helpers.
 *
 * @since  1.3.1
 */
class LessonModelTest extends TestCase
{
    public function testGetDatesIncludesLessonEndDateWhenItIsALessonDay(): void
    {
        $dates = LessonModel::getDates(
            '2026-05-01',
            '2026-05-08',
            [
                'Monday' => 0,
                'Tuesday' => 0,
                'Wednesday' => 0,
                'Thursday' => 0,
                'Friday' => 1,
                'Saturday' => 0,
                'Sunday' => 0,
            ]
        );

        $this->assertSame(
            ['2026-05-01', '2026-05-08'],
            array_map(static fn(\DateTimeInterface $date): string => $date->format('Y-m-d'), $dates)
        );
    }

    public function testGetDatesReturnsEmptyArrayWhenNoDaysMatch(): void
    {
        $dates = LessonModel::getDates(
            '2026-05-04',
            '2026-05-10',
            [
                'Monday' => 0,
                'Tuesday' => 0,
                'Wednesday' => 0,
                'Thursday' => 0,
                'Friday' => 0,
                'Saturday' => 0,
                'Sunday' => 0,
            ]
        );

        $this->assertSame([], $dates);
    }

    public function testGetDatesWithMultipleMatchingDaysInSameWeek(): void
    {
        // 2026-05-04 (Mon) through 2026-05-08 (Fri): lessons on Mon and Wed.
        $dates = LessonModel::getDates(
            '2026-05-04',
            '2026-05-08',
            [
                'Monday' => 1,
                'Tuesday' => 0,
                'Wednesday' => 1,
                'Thursday' => 0,
                'Friday' => 0,
                'Saturday' => 0,
                'Sunday' => 0,
            ]
        );

        $this->assertSame(
            ['2026-05-04', '2026-05-06'],
            array_map(static fn(\DateTimeInterface $date): string => $date->format('Y-m-d'), $dates)
        );
    }

    public function testGetDatesExcludesEndDateWhenItIsNotALessonDay(): void
    {
        // 2026-05-04 (Mon) through 2026-05-10 (Sun): Friday-only lessons.
        // Only 2026-05-08 (Friday) qualifies; the Sunday end date does not.
        $dates = LessonModel::getDates(
            '2026-05-04',
            '2026-05-10',
            [
                'Monday' => 0,
                'Tuesday' => 0,
                'Wednesday' => 0,
                'Thursday' => 0,
                'Friday' => 1,
                'Saturday' => 0,
                'Sunday' => 0,
            ]
        );

        $this->assertSame(
            ['2026-05-08'],
            array_map(static fn(\DateTimeInterface $date): string => $date->format('Y-m-d'), $dates)
        );
    }

    public function testGetLesdaysMondayOnlyBitmask(): void
    {
        // Monday flag = 64
        $lesdays = LessonModel::getLesdays(64);

        $this->assertSame(1, $lesdays['Monday']);
        $this->assertSame(0, $lesdays['Tuesday']);
        $this->assertSame(0, $lesdays['Wednesday']);
        $this->assertSame(0, $lesdays['Thursday']);
        $this->assertSame(0, $lesdays['Friday']);
        $this->assertSame(0, $lesdays['Saturday']);
        $this->assertSame(0, $lesdays['Sunday']);
    }

    public function testGetLesdaysMultipleDayBitmask(): void
    {
        // Monday (64) + Friday (4) = 68
        $lesdays = LessonModel::getLesdays(68);

        $this->assertSame(1, $lesdays['Monday']);
        $this->assertSame(0, $lesdays['Tuesday']);
        $this->assertSame(0, $lesdays['Wednesday']);
        $this->assertSame(0, $lesdays['Thursday']);
        $this->assertSame(1, $lesdays['Friday']);
        $this->assertSame(0, $lesdays['Saturday']);
        $this->assertSame(0, $lesdays['Sunday']);
    }

    public function testGetLesdaysZeroBitmaskYieldsAllZero(): void
    {
        $lesdays = LessonModel::getLesdays(0);

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $this->assertSame(0, $lesdays[$day], "Expected 0 for $day with bitmask 0");
        }
    }

    public function testGetLesdaysFullBitmaskYieldsAllOne(): void
    {
        // Mon(64)+Tue(32)+Wed(16)+Thu(8)+Fri(4)+Sat(2)+Sun(1) = 127
        $lesdays = LessonModel::getLesdays(127);

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $this->assertSame(1, $lesdays[$day], "Expected 1 for $day with full bitmask");
        }
    }

    public function testGetLesdaysBitmaskCoversAllSevenDayKeys(): void
    {
        $lesdays = LessonModel::getLesdays(0);

        $this->assertArrayHasKey('Monday', $lesdays);
        $this->assertArrayHasKey('Tuesday', $lesdays);
        $this->assertArrayHasKey('Wednesday', $lesdays);
        $this->assertArrayHasKey('Thursday', $lesdays);
        $this->assertArrayHasKey('Friday', $lesdays);
        $this->assertArrayHasKey('Saturday', $lesdays);
        $this->assertArrayHasKey('Sunday', $lesdays);
    }

    public function testHasConfiguredLesdaysIsFalseForEmptyBitmask(): void
    {
        $this->assertFalse(LessonModel::hasConfiguredLesdays(LessonModel::getLesdays(0)));
    }

    public function testHasConfiguredLesdaysIsTrueWhenAWeekdayIsSelected(): void
    {
        $this->assertTrue(LessonModel::hasConfiguredLesdays(LessonModel::getLesdays(64)));
    }

    public function testIsValidLessonPeriodAcceptsInclusiveStartAndEnd(): void
    {
        $this->assertTrue(LessonModel::isValidLessonPeriod('2026-09-01', '2026-09-30'));
        $this->assertTrue(LessonModel::isValidLessonPeriod('2026-09-09', '2026-09-09'));
    }

    public function testIsValidLessonPeriodRejectsMissingOrInvertedDates(): void
    {
        $this->assertFalse(LessonModel::isValidLessonPeriod('', '2026-09-30'));
        $this->assertFalse(LessonModel::isValidLessonPeriod('2026-09-01', ''));
        $this->assertFalse(LessonModel::isValidLessonPeriod('0000-00-00', '2026-09-30'));
        $this->assertFalse(LessonModel::isValidLessonPeriod('2026-10-01', '2026-09-01'));
    }

    public function testParseLessonDateNormalizesMidnightAndRejectsInvalidValues(): void
    {
        $parsed = LessonModel::parseLessonDate('2026-09-09 14:30:00');

        $this->assertInstanceOf(\DateTime::class, $parsed);
        $this->assertSame('2026-09-09 00:00:00', $parsed->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-09', LessonModel::parseLessonDate('09/09/2026')?->format('Y-m-d'));
        $this->assertSame('2026-09-09', LessonModel::parseLessonDate('09-09-2026')?->format('Y-m-d'));
        $this->assertSame('2026-09-01', LessonModel::parseLessonDate('1/9/2026')?->format('Y-m-d'));
        $this->assertSame('2026-09-09', LessonModel::parseLessonDate('09.09.2026')?->format('Y-m-d'));
        $this->assertSame('2026-09-09', LessonModel::parseLessonDate('2026-09-09T14:30:00+02:00')?->format('Y-m-d'));
        $this->assertSame('2026-09-01', LessonModel::parseLessonDate('01/09/2026 00:00:00')?->format('Y-m-d'));
        $this->assertNull(LessonModel::parseLessonDate(null));
        $this->assertNull(LessonModel::parseLessonDate('not-a-date'));
        $this->assertNull(LessonModel::parseLessonDate('32/13/2026'));
    }

    public function testParseLessonDateAcceptsDateTimeObjects(): void
    {
        $parsed = LessonModel::parseLessonDate(new \DateTime('2026-09-09 18:45:00'));

        $this->assertSame('2026-09-09 00:00:00', $parsed?->format('Y-m-d H:i:s'));
    }

    public function testIsValidLessonPeriodAcceptsBelgianFormattedDates(): void
    {
        $this->assertTrue(LessonModel::isValidLessonPeriod('01/09/2026', '30/06/2027'));
        $this->assertNotNull(LessonModel::periodFromValues('01-09-2026', '30-06-2027'));
        $this->assertNotNull(LessonModel::periodFromValues('1/9/2026', '30/6/2027'));
    }

    public function testEmptyBitmaskDoesNotPreventUsingTheLessonPeriod(): void
    {
        $this->assertTrue(LessonModel::isValidLessonPeriod('2026-09-01', '2026-09-30'));
        $this->assertFalse(LessonModel::hasConfiguredLesdays(LessonModel::getLesdays(0)));
        $this->assertSame(
            [],
            LessonModel::getDates('2026-09-01', '2026-09-30', LessonModel::getLesdays(0))
        );
    }
}
