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
}
