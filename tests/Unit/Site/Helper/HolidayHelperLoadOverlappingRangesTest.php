<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\HolidayHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HolidayHelper::loadOverlappingRanges().
 *
 * loadOverlappingRanges() is called by LessonModel::getHolidayIsoRanges() to
 * determine which dates must be greyed out in the attendance date pickers.
 * A bug here silently allows attendance records on school holidays.
 *
 * @since  1.3.24
 */
final class HolidayHelperLoadOverlappingRangesTest extends TestCase
{
    public function testEmptyFromIsoReturnsEmptyArrayWithoutQueryingDb(): void
    {
        $db = $this->makeDb([]);

        $this->assertSame([], HolidayHelper::loadOverlappingRanges($db, '', '2026-09-30'));
    }

    public function testEmptyToIsoReturnsEmptyArrayWithoutQueryingDb(): void
    {
        $db = $this->makeDb([]);

        $this->assertSame([], HolidayHelper::loadOverlappingRanges($db, '2026-09-01', ''));
    }

    public function testBothDatesEmptyReturnsEmptyArray(): void
    {
        $db = $this->makeDb([]);

        $this->assertSame([], HolidayHelper::loadOverlappingRanges($db, '', ''));
    }

    public function testDbExceptionReturnsEmptyArrayInsteadOfPropagating(): void
    {
        $db = $this->makeDb([], throws: true);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-09-01', '2026-09-30');

        $this->assertSame([], $result);
    }

    public function testEmptyRowListReturnsEmptyArray(): void
    {
        $db = $this->makeDb([]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-09-01', '2026-09-30');

        $this->assertSame([], $result);
    }

    public function testRowsReturnedAsStartEndPairs(): void
    {
        $db = $this->makeDb([
            ['start' => '2026-12-21', 'end' => '2027-01-03'],
            ['start' => '2026-04-04', 'end' => '2026-04-11'],
        ]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-01-01', '2027-12-31');

        $this->assertCount(2, $result);
        $this->assertSame('2026-12-21', $result[0]['start']);
        $this->assertSame('2027-01-03', $result[0]['end']);
        $this->assertSame('2026-04-04', $result[1]['start']);
        $this->assertSame('2026-04-11', $result[1]['end']);
    }

    public function testDatetimeValuesAreStrippedToDateOnly(): void
    {
        $db = $this->makeDb([
            ['start' => '2026-12-21 00:00:00', 'end' => '2027-01-03 00:00:00'],
        ]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-01-01', '2027-12-31');

        $this->assertCount(1, $result);
        $this->assertSame('2026-12-21', $result[0]['start']);
        $this->assertSame('2027-01-03', $result[0]['end']);
    }

    public function testRowWithInvalidStartDateIsSkipped(): void
    {
        $db = $this->makeDb([
            ['start' => 'not-a-date', 'end' => '2026-09-10'],
            ['start' => '2026-09-15', 'end' => '2026-09-20'],
        ]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $result);
        $this->assertSame('2026-09-15', $result[0]['start']);
        $this->assertSame('2026-09-20', $result[0]['end']);
    }

    public function testRowWithInvalidEndDateIsSkipped(): void
    {
        $db = $this->makeDb([
            ['start' => '2026-09-01', 'end' => 'invalid'],
            ['start' => '2026-09-15', 'end' => '2026-09-20'],
        ]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $result);
        $this->assertSame('2026-09-15', $result[0]['start']);
    }

    public function testRowWithZeroStartDateIsSkipped(): void
    {
        $db = $this->makeDb([
            ['start' => '0000-00-00', 'end' => '2026-09-10'],
            ['start' => '2026-09-15', 'end' => '2026-09-20'],
        ]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $result);
        $this->assertSame('2026-09-15', $result[0]['start']);
    }

    public function testSingleValidRowIsReturned(): void
    {
        $db = $this->makeDb([
            ['start' => '2026-10-26', 'end' => '2026-11-01'],
        ]);

        $result = HolidayHelper::loadOverlappingRanges($db, '2026-10-01', '2026-11-30');

        $this->assertCount(1, $result);
        $this->assertSame(['start' => '2026-10-26', 'end' => '2026-11-01'], $result[0]);
    }

    private function makeDb(array $rows, bool $throws = false): object
    {
        return new class ($rows, $throws) {
            public function __construct(
                private readonly array $rows,
                private readonly bool $throws
            ) {
            }

            public function getQuery(bool $new): object
            {
                return new class {
                    public function select(mixed $c): static
                    {
                        return $this;
                    }

                    public function from(mixed $t): static
                    {
                        return $this;
                    }

                    public function where(mixed $w): static
                    {
                        return $this;
                    }
                };
            }

            public function quoteName(mixed $name, mixed $alias = null): string
            {
                return "`$name`";
            }

            public function quote(mixed $value): string
            {
                return "'$value'";
            }

            public function setQuery(mixed $query): static
            {
                return $this;
            }

            public function loadAssocList(): array
            {
                if ($this->throws) {
                    throw new \RuntimeException('DB error');
                }

                return $this->rows;
            }
        };
    }
}
