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

    public function testZeroOffsetReturnsSameCalendarYear(): void
    {
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-05-15', 0));
    }

    public function testNegativeOffsetIsClampedToZero(): void
    {
        // Negative offsets are clamped to 0 — same result as a zero-offset call.
        $this->assertSame(2026, SchoolYearHelper::calculateSchoolYear('2026-05-15', -3));
    }

    public function testOneMonthOffsetMovesJanuaryToPreviousYear(): void
    {
        // 2026-01-15 minus 1 month = 2025-12-15 → school year 2025.
        $this->assertSame(2025, SchoolYearHelper::calculateSchoolYear('2026-01-15', 1));
    }

    public function testTwelveMonthOffsetReturnsPreviousCalendarYear(): void
    {
        // 2026-07-01 minus 12 months = 2025-07-01 → school year 2025.
        $this->assertSame(2025, SchoolYearHelper::calculateSchoolYear('2026-07-01', 12));
    }

    public function testSixMonthOffsetWithJanuaryFirstFallsInPreviousYear(): void
    {
        // 2026-01-01 minus 6 months = 2025-07-01 → school year 2025.
        $this->assertSame(2025, SchoolYearHelper::calculateSchoolYear('2026-01-01', 6));
    }
}
