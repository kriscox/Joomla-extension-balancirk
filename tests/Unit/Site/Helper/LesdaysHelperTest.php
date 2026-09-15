<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Helper;

use DateTime;
use PHPUnit\Framework\TestCase;
use CoCoCo\Component\Balancirk\Site\Helper\LesdaysHelper;

/**
 * Tests for lesson-day bitmask encoding and decoding.
 *
 * @since  1.3.22
 */
class LesdaysHelperTest extends TestCase
{
    public function testToWeekdaysDecodesMondayOnly(): void
    {
        $weekdays = LesdaysHelper::toWeekdays(64);

        $this->assertSame(1, $weekdays['Monday']);
        $this->assertSame(0, $weekdays['Sunday']);
        $this->assertSame(0, $weekdays['Friday']);
    }

    public function testToWeekdaysDecodesSundayOnly(): void
    {
        $weekdays = LesdaysHelper::toWeekdays(1);

        $this->assertSame(1, $weekdays['Sunday']);
        $this->assertSame(0, $weekdays['Monday']);
    }

    public function testToWeekdaysDecodesMultipleDays(): void
    {
        // Monday + Wednesday + Friday (experts / a-la-carte style lessons).
        $weekdays = LesdaysHelper::toWeekdays(64 + 16 + 4);

        $this->assertSame(1, $weekdays['Monday']);
        $this->assertSame(0, $weekdays['Tuesday']);
        $this->assertSame(1, $weekdays['Wednesday']);
        $this->assertSame(0, $weekdays['Thursday']);
        $this->assertSame(1, $weekdays['Friday']);
        $this->assertSame(0, $weekdays['Saturday']);
        $this->assertSame(0, $weekdays['Sunday']);
    }

    public function testToFormValuesReturnsEverySelectedBitAsString(): void
    {
        $this->assertSame(['64', '16', '4'], LesdaysHelper::toFormValues(84));
        $this->assertSame(['1'], LesdaysHelper::toFormValues(1));
        $this->assertSame([], LesdaysHelper::toFormValues(0));
    }

    public function testToFormValuesDoesNotUseACommaSeparatedString(): void
    {
        $values = LesdaysHelper::toFormValues(64 + 32);

        $this->assertIsArray($values);
        $this->assertContains('64', $values);
        $this->assertContains('32', $values);
        $this->assertTrue(in_array('64', $values, true));
        $this->assertTrue(in_array('32', $values, true));

        // The previous implementation returned "64, 32, ". Joomla checkboxes
        // treat a string as a single value, so neither option would match.
        $legacy = (array) '64, 32, ';
        $this->assertFalse(in_array('64', $legacy, true));
        $this->assertFalse(in_array('32', $legacy, true));
    }

    public function testFromFormValuesRoundTripsMultipleDays(): void
    {
        $mask = 64 + 32 + 16;
        $this->assertSame($mask, LesdaysHelper::fromFormValues(LesdaysHelper::toFormValues($mask)));
    }

    public function testFromFormValuesORsBitsAndIgnoresInvalidValues(): void
    {
        $this->assertSame(96, LesdaysHelper::fromFormValues(['64', '32', '64', '99']));
        $this->assertSame(0, LesdaysHelper::fromFormValues(null));
        $this->assertSame(0, LesdaysHelper::fromFormValues([]));
    }

    public function testFromFormValuesAcceptsLegacyCommaSeparatedString(): void
    {
        $this->assertSame(96, LesdaysHelper::fromFormValues('64, 32, '));
        $this->assertSame(127, LesdaysHelper::fromFormValues('64,32,16,8,4,2,1'));
    }

    public function testHasConfiguredDays(): void
    {
        $this->assertFalse(LesdaysHelper::hasConfiguredDays(LesdaysHelper::toWeekdays(0)));
        $this->assertTrue(LesdaysHelper::hasConfiguredDays(LesdaysHelper::toWeekdays(4)));
    }

    public function testMatchesDateUsesEnglishWeekdayBits(): void
    {
        $mondayAndFriday = 64 + 4;

        $this->assertTrue(LesdaysHelper::matchesDate(new DateTime('2026-05-04'), $mondayAndFriday));
        $this->assertFalse(LesdaysHelper::matchesDate(new DateTime('2026-05-05'), $mondayAndFriday));
        $this->assertTrue(LesdaysHelper::matchesDate(new DateTime('2026-05-08'), $mondayAndFriday));
        $this->assertFalse(LesdaysHelper::matchesDate(new DateTime('2026-05-10'), $mondayAndFriday));
    }

    public function testJsGetDayBitsAlignWithPhpWeekdays(): void
    {
        // JavaScript Date.getDay(): 0=Sunday, 1=Monday, ..., 6=Saturday.
        $this->assertSame(1, LesdaysHelper::JS_GETDAY_BITS[0]);
        $this->assertSame(64, LesdaysHelper::JS_GETDAY_BITS[1]);
        $this->assertSame(32, LesdaysHelper::JS_GETDAY_BITS[2]);
        $this->assertSame(16, LesdaysHelper::JS_GETDAY_BITS[3]);
        $this->assertSame(8, LesdaysHelper::JS_GETDAY_BITS[4]);
        $this->assertSame(4, LesdaysHelper::JS_GETDAY_BITS[5]);
        $this->assertSame(2, LesdaysHelper::JS_GETDAY_BITS[6]);

        $mask = 64 + 16 + 4;
        $this->assertSame(64, $mask & LesdaysHelper::JS_GETDAY_BITS[1]);
        $this->assertSame(0, $mask & LesdaysHelper::JS_GETDAY_BITS[2]);
        $this->assertSame(16, $mask & LesdaysHelper::JS_GETDAY_BITS[3]);
        $this->assertSame(4, $mask & LesdaysHelper::JS_GETDAY_BITS[5]);
        $this->assertSame(0, $mask & LesdaysHelper::JS_GETDAY_BITS[0]);
    }

    public function testFullWeekBitmaskIs127(): void
    {
        $this->assertSame(127, array_sum(LesdaysHelper::DAYS));
        $this->assertSame(127, LesdaysHelper::fromFormValues(['64', '32', '16', '8', '4', '2', '1']));
    }
}
