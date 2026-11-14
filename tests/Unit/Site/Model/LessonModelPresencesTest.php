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
 * Tests for LessonModel::getPresences().
 *
 * getPresences() runs a simple GROUP-BY query that counts how many students
 * were present on each lesson date.  The lesson id is sourced from model
 * state rather than a parameter.
 *
 * @since  1.3.26
 */
class LessonModelPresencesTest extends TestCase
{
    /**
     * getPresences() must return the rows provided by the database.
     *
     * @return void
     */
    public function testGetPresencesReturnsRowsFromDatabase(): void
    {
        $expected = [
            (object) ['date' => '2026-10-01', 'count' => 5],
            (object) ['date' => '2026-10-08', 'count' => 3],
        ];

        [$db] = $this->makeFakeDb($expected, 42);
        $model = $this->makeModel($db, 42);

        $this->assertSame($expected, $model->getPresences());
    }

    /**
     * getPresences() must return an empty array when the database returns no rows.
     *
     * @return void
     */
    public function testGetPresencesReturnsEmptyArrayWhenNoRows(): void
    {
        [$db] = $this->makeFakeDb([], 1);
        $model = $this->makeModel($db, 1);

        $this->assertSame([], $model->getPresences());
    }

    /**
     * getPresences() must pass the current lesson id from model state to the
     * WHERE clause so that only presences for the right lesson are counted.
     *
     * @return void
     */
    public function testGetPresencesFiltersOnLessonIdFromState(): void
    {
        [, $qb] = $this->makeFakeDb([], 7);
        $model = $this->makeModel(
            new class ($qb) {
                public function __construct(private readonly object $qb)
                {
                }

                public function getQuery(bool $new): object
                {
                    return $this->qb;
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    return is_array($n) ? array_map(static fn($x) => "`$x`", $n) : "`$n`";
                }

                public function quote(mixed $value): string
                {
                    return "'" . $value . "'";
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function loadObjectList(): array
                {
                    return [];
                }
            },
            7
        );

        $model->getPresences();

        // The WHERE clause should embed the lesson id via quote()
        $this->assertStringContainsString("'7'", $qb->capturedWhere);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a fake database and query builder for getPresences().
     *
     * @param   array  $rows      Rows returned by loadObjectList().
     * @param   int    $lessonId  The lesson id the WHERE condition should contain.
     *
     * @return  array{0: object, 1: object}
     */
    private function makeFakeDb(array $rows, int $lessonId): array
    {
        $qb = new class {
            public string $capturedWhere = '';

            public function select(mixed $x): static
            {
                return $this;
            }

            public function from(mixed $t): static
            {
                return $this;
            }

            public function where(mixed $cond): static
            {
                $this->capturedWhere = (string) $cond;

                return $this;
            }

            public function group(mixed ...$args): static
            {
                return $this;
            }
        };

        $db = new class ($qb, $rows) {
            public function __construct(
                private readonly object $qb,
                private readonly array $rows
            ) {
            }

            public function getQuery(bool $new): object
            {
                return $this->qb;
            }

            public function quoteName(mixed $n, mixed $a = null): mixed
            {
                return is_array($n) ? array_map(static fn($x) => "`$x`", $n) : "`$n`";
            }

            public function quote(mixed $value): string
            {
                return "'" . $value . "'";
            }

            public function setQuery(mixed $q): static
            {
                return $this;
            }

            public function loadObjectList(): array
            {
                return $this->rows;
            }
        };

        return [$db, $qb];
    }

    /**
     * Create a LessonModel stub that uses the given database and returns
     * $lessonId for the 'lesson.id' state property.
     *
     * @param   object  $db        Fake database.
     * @param   int     $lessonId  State value for 'lesson.id'.
     *
     * @return  LessonModel
     */
    private function makeModel(object $db, int $lessonId): LessonModel
    {
        return new class ($db, $lessonId) extends LessonModel {
            public function __construct(
                private readonly object $db,
                private readonly int $lessonId
            ) {
            }

            public function getDatabase(): object
            {
                return $this->db;
            }

            public function getState($property = null, $default = null): mixed
            {
                if ($property === 'lesson.id') {
                    return $this->lessonId;
                }

                return $default;
            }
        };
    }
}
