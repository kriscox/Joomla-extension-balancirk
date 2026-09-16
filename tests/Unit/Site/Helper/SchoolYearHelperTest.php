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

    public function testResolveListFilterYearDefaultsEmptyToCurrentSchoolYear(): void
    {
        $this->assertSame('2025', SchoolYearHelper::resolveListFilterYear('', '2026-06-30', 6));
        $this->assertSame('2026', SchoolYearHelper::resolveListFilterYear(null, '2026-07-01', 6));
    }

    public function testResolveListFilterYearKeepsAnExplicitYear(): void
    {
        $this->assertSame('2024', SchoolYearHelper::resolveListFilterYear('2024', '2026-07-01', 6));
        $this->assertSame('2023', SchoolYearHelper::resolveListFilterYear(' 2023 ', '2026-07-01', 6));
    }

    public function testResolveListFilterYearTreatsStarAsAllYears(): void
    {
        $this->assertNull(SchoolYearHelper::resolveListFilterYear(SchoolYearHelper::ALL_YEARS_FILTER, '2026-07-01', 6));
        $this->assertSame('*', SchoolYearHelper::ALL_YEARS_FILTER);
    }

    public function testEnsureYearInListPrependsMissingSchoolYear(): void
    {
        $this->assertSame(['2026', '2025', '2024'], SchoolYearHelper::ensureYearInList(['2025', '2024'], 2026));
    }

    public function testEnsureYearInListDoesNotDuplicateExistingYear(): void
    {
        $this->assertSame(['2026', '2025'], SchoolYearHelper::ensureYearInList(['2026', '2025'], '2026'));
    }

    public function testFormFilterYearNeverReturnsEmpty(): void
    {
        $this->assertSame('2026', SchoolYearHelper::formFilterYear('', '2026-09-15', 6));
        $this->assertSame('2025', SchoolYearHelper::formFilterYear(null, '2026-06-30', 6));
        $this->assertSame('*', SchoolYearHelper::formFilterYear('*', '2026-09-15', 6));
    }

    public function testNormaliseYearListCastsDecimalYears(): void
    {
        $this->assertSame(['2026', '2025'], SchoolYearHelper::normaliseYearList(['2026.0000', '2025', '2026']));
    }

    public function testEnsureYearInListTreatsDecimalAsSameYear(): void
    {
        $this->assertSame(['2026', '2025'], SchoolYearHelper::ensureYearInList(['2026.0000', '2025'], 2026));
    }

    public function testPersistListFilterYearWritesNestedFilterArray(): void
    {
        $app = new class {
            public array $state = [];

            public function getUserState(string $key, mixed $default = null): mixed
            {
                return $this->state[$key] ?? $default;
            }

            public function setUserState(string $key, mixed $value): void
            {
                $this->state[$key] = $value;
            }
        };

        SchoolYearHelper::persistListFilterYear($app, 'com_balancirk.lessons', '2026');

        $this->assertSame('2026', $app->state['com_balancirk.lessons.filter.year']);
        $this->assertSame('2026', $app->state['com_balancirk.lessons.filter']['year']);
    }

    public function testIsCurrentOrFutureYearAcceptsCurrentAndLaterYears(): void
    {
        $this->assertTrue(SchoolYearHelper::isCurrentOrFutureYear(2026, '2026-09-15', 6));
        $this->assertTrue(SchoolYearHelper::isCurrentOrFutureYear(2027, '2026-09-15', 6));
        $this->assertTrue(SchoolYearHelper::isCurrentOrFutureYear('2026.0000', '2026-09-15', 6));
    }

    public function testIsCurrentOrFutureYearRejectsPastAndEmptyYears(): void
    {
        $this->assertFalse(SchoolYearHelper::isCurrentOrFutureYear(2025, '2026-09-15', 6));
        $this->assertFalse(SchoolYearHelper::isCurrentOrFutureYear(0, '2026-09-15', 6));
        $this->assertFalse(SchoolYearHelper::isCurrentOrFutureYear('', '2026-09-15', 6));
        $this->assertFalse(SchoolYearHelper::isCurrentOrFutureYear(null, '2026-09-15', 6));
    }
}
