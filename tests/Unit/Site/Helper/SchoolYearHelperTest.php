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
use CoCoCo\Component\Balancirk\Site\Helper\SchoolYearHelper;

/**
 * Test class for school year calculations.
 *
 * @since  1.3.8
 */
class SchoolYearHelperTest extends TestCase
{
    public function testDefaultOffsetIsSixMonths(): void
    {
        $this->assertSame(6, SchoolYearHelper::DEFAULT_OFFSET_MONTHS);
    }

    public function testSixMonthOffsetKeepsJuneInPreviousSchoolYear(): void
    {
        $this->assertSame(2025, SchoolYearHelper::calculateSchoolYear('2026-06-30', 6));
    }

    public function testSixMonthOffsetStartsNewSchoolYearInJuly(): void
    {
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-07-01', 6));
    }

    public function testZeroOffsetUsesCalendarYearDirectly(): void
    {
        // Zero offset means the school year equals the calendar year.
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-01-01', 0));
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-12-31', 0));
    }

    public function testNegativeOffsetIsClampedToZero(): void
    {
        // Negative offsets must be treated as zero (no subtraction).
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-03-15', -3));
    }

    public function testTwelveMonthOffsetShiftsSchoolYearByOneFullYear(): void
    {
        // Subtracting 12 months from a date always gives the prior calendar year.
        $this->assertSame(2025, SchoolYearHelper::calculateSchoolYear('2026-06-15', 12));
    }

    public function testJanuaryWithSixMonthOffsetBelongsToLastCalendarYear(): void
    {
        // January 2026 minus 6 months = July 2025 → school year 2025.
        $this->assertSame(2025, SchoolYearHelper::calculateSchoolYear('2026-01-15', 6));
    }

    public function testDecemberWithSixMonthOffsetBelongsToCurrentCalendarYear(): void
    {
        // December 2026 minus 6 months = June 2026 → school year 2026.
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-12-01', 6));
    }}
