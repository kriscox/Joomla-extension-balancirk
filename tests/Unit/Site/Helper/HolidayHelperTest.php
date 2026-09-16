<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\HolidayHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for holiday range matching.
 *
 * @since  1.3.24
 */
class HolidayHelperTest extends TestCase
{
    public function testContainsIsoDateUsesInclusiveBounds(): void
    {
        $ranges = [['start' => '2026-09-01', 'end' => '2026-09-06']];

        $this->assertTrue(HolidayHelper::containsIsoDate('2026-09-01', $ranges));
        $this->assertTrue(HolidayHelper::containsIsoDate('2026-09-03', $ranges));
        $this->assertTrue(HolidayHelper::containsIsoDate('2026-09-06', $ranges));
        $this->assertFalse(HolidayHelper::containsIsoDate('2026-08-31', $ranges));
        $this->assertFalse(HolidayHelper::containsIsoDate('2026-09-07', $ranges));
        $this->assertFalse(HolidayHelper::containsIsoDate('2026-09-03', []));
    }

    public function testContainsIsoDateReadsDatetimeColumnValues(): void
    {
        $ranges = [['start' => '2026-12-21 00:00:00', 'end' => '2027-01-03 00:00:00']];

        $this->assertTrue(HolidayHelper::containsIsoDate('2026-12-25', $ranges));
        $this->assertTrue(HolidayHelper::containsIsoDate('2027-01-03', $ranges));
        $this->assertFalse(HolidayHelper::containsIsoDate('2027-01-04', $ranges));
    }
}
