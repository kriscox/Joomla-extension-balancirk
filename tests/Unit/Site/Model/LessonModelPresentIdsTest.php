<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

declare(strict_types=1);

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Tests for getPresentStudentIds() and getTeachedTeacherIds().
 *
 * These methods pre-populate the attendance form checkboxes with the ids of
 * students and teachers already marked present for a lesson on a given date.
 * A guard-clause failure silently returns [] (all unchecked), or a type error
 * in the int cast could corrupt attendance data.
 *
 * @since  1.3.20
 */
final class LessonModelPresentIdsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getPresentStudentIds() — guard clauses
    // -------------------------------------------------------------------------

    public function testGetPresentStudentIdsReturnsEmptyForZeroLessonId(): void
    {
        $model = $this->makeModel([]);

        $this->assertSame([], $model->getPresentStudentIds(0, '2026-09-07'));
    }

    public function testGetPresentStudentIdsReturnsEmptyForNegativeLessonId(): void
    {
        $model = $this->makeModel([]);

        $this->assertSame([], $model->getPresentStudentIds(-1, '2026-09-07'));
    }

    public function testGetPresentStudentIdsReturnsEmptyForEmptyDate(): void
    {
        $model = $this->makeModel([]);

        $this->assertSame([], $model->getPresentStudentIds(5, ''));
    }

    // -------------------------------------------------------------------------
    // getPresentStudentIds() — DB path
    // -------------------------------------------------------------------------

    public function testGetPresentStudentIdsReturnsMappedIntegers(): void
    {
        $model = $this->makeModel(['10', '12', '15']);

        $result = $model->getPresentStudentIds(5, '2026-09-07');

        $this->assertSame([10, 12, 15], $result);
    }

    public function testGetPresentStudentIdsReturnsEmptyWhenNoPresences(): void
    {
        $model = $this->makeModel([]);

        $result = $model->getPresentStudentIds(5, '2026-09-07');

        $this->assertSame([], $result);
    }

    public function testGetPresentStudentIdsAlwaysReturnsIntegers(): void
    {
        $model = $this->makeModel(['7']);

        $result = $model->getPresentStudentIds(5, '2026-09-07');

        $this->assertCount(1, $result);
        $this->assertIsInt($result[0]);
        $this->assertSame(7, $result[0]);
    }

    // -------------------------------------------------------------------------
    // getTeachedTeacherIds() — guard clauses
    // -------------------------------------------------------------------------

    public function testGetTeachedTeacherIdsReturnsEmptyForZeroLessonId(): void
    {
        $model = $this->makeModel([]);

        $this->assertSame([], $model->getTeachedTeacherIds(0, '2026-09-07'));
    }

    public function testGetTeachedTeacherIdsReturnsEmptyForNegativeLessonId(): void
    {
        $model = $this->makeModel([]);

        $this->assertSame([], $model->getTeachedTeacherIds(-5, '2026-09-07'));
    }

    public function testGetTeachedTeacherIdsReturnsEmptyForEmptyDate(): void
    {
        $model = $this->makeModel([]);

        $this->assertSame([], $model->getTeachedTeacherIds(5, ''));
    }

    // -------------------------------------------------------------------------
    // getTeachedTeacherIds() — DB path
    // -------------------------------------------------------------------------

    public function testGetTeachedTeacherIdsReturnsMappedIntegers(): void
    {
        $model = $this->makeModel(['3', '7']);

        $result = $model->getTeachedTeacherIds(5, '2026-09-07');

        $this->assertSame([3, 7], $result);
    }

    public function testGetTeachedTeacherIdsReturnsEmptyWhenNoTeachedRows(): void
    {
        $model = $this->makeModel([]);

        $result = $model->getTeachedTeacherIds(5, '2026-09-07');

        $this->assertSame([], $result);
    }

    public function testGetTeachedTeacherIdsAlwaysReturnsIntegers(): void
    {
        $model = $this->makeModel(['42']);

        $result = $model->getTeachedTeacherIds(5, '2026-09-07');

        $this->assertCount(1, $result);
        $this->assertIsInt($result[0]);
        $this->assertSame(42, $result[0]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Build a model whose database returns $columnValues from loadColumn().
     *
     * @param   string[]  $columnValues  Values returned by DB loadColumn().
     */
    private function makeModel(array $columnValues): LessonModel
    {
        $db = new class ($columnValues) {
            public function __construct(private readonly array $columnValues)
            {
            }

            public function getQuery(bool $new): object
            {
                return new class {
                    public function select(mixed $x): static
                    {
                        return $this;
                    }

                    public function from(mixed $t): static
                    {
                        return $this;
                    }

                    public function where(mixed $c): static
                    {
                        return $this;
                    }

                    public function order(mixed $o): static
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

            public function loadColumn(): array
            {
                return $this->columnValues;
            }
        };

        return new class ($db) extends LessonModel {
            public function __construct(private readonly object $db)
            {
            }

            public function getDatabase(): object
            {
                return $this->db;
            }

            public function getState($property = null, $default = null): mixed
            {
                return null;
            }
        };
    }
}
