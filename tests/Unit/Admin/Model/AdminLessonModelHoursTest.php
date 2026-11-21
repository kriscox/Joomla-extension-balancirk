<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required for loading the Admin LessonModel class.
// Bracketed namespace syntax is required when mixing multiple namespace blocks.
//
// IMPORTANT ORDERING: This file (AdminLessonModelH...) sorts before
// AdminLessonModelStudentsTest.php (S) and AdminLessonModelValidateTest.php (V)
// alphabetically, so it runs first and is responsible for defining AdminModel
// with ALL methods needed by the later files in this directory:
//   - setError() / getError()  — used by validate() error paths (V)
//   - validate()               — called by AdminLessonModel::validate() (V)
//   - getState()               — used by LessonModel getXxx() methods (S)
// ---------------------------------------------------------------------------

namespace Joomla\CMS\MVC\Model {
    if (!class_exists('Joomla\\CMS\\MVC\\Model\\AdminModel')) {
        abstract class AdminModel
        {
            // No typed property declarations — subclasses freely redeclare them.
            public function setError(string $error): void
            {
            }

            public function getError(): string
            {
                return '';
            }

            /**
             * Default stub: return $data unchanged so AdminLessonModel::validate()
             * can call parent::validate() without the full Joomla form-validation stack.
             */
            public function validate($form, $data, $group = null)
            {
                return $data;
            }

            public function getState($property = null, $default = null): mixed
            {
                return $default;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\LessonModel as AdminLessonModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for AdminLessonModel::getHours().
     *
     * getHours() queries #__balancirk_hours for the time slots belonging to a
     * lesson.  The lesson id is taken from the explicit argument when given, or
     * falls back to getState('lesson.id').  The raw row list from the database
     * is returned unchanged.
     *
     * Regression risk: a wrong lessonId in the WHERE clause silently returns
     * the wrong set of hours (or an empty set), breaking the lesson edit form.
     *
     * @since  1.3.25
     */
    class AdminLessonModelHoursTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // Explicit lesson id
        // -----------------------------------------------------------------------

        /**
         * When an explicit lesson id is passed the query WHERE clause must
         * contain that id so the correct hours are fetched.
         *
         * @return void
         */
        public function testGetHoursUsesExplicitLessonId(): void
        {
            [$db, $qb] = $this->makeHoursDb([]);
            $model = $this->makeModelWithDb($db);

            $model->getHours(42);

            $this->assertStringContainsString('42', $qb->capturedWhere);
        }

        /**
         * getHours() must return the rows provided by the database unchanged.
         *
         * @return void
         */
        public function testGetHoursReturnsRowsFromDatabase(): void
        {
            $expected = [[1, 2], [2, 4]];
            [$db] = $this->makeHoursDb($expected);
            $model = $this->makeModelWithDb($db);

            $this->assertSame($expected, $model->getHours(5));
        }

        /**
         * getHours() must return an empty array (not false or null) when the
         * database returns no rows for the given lesson.
         *
         * @return void
         */
        public function testGetHoursReturnsEmptyArrayWhenDbReturnsNoRows(): void
        {
            [$db] = $this->makeHoursDb([]);
            $model = $this->makeModelWithDb($db);

            $this->assertSame([], $model->getHours(7));
        }

        // -----------------------------------------------------------------------
        // getState() fallback
        // -----------------------------------------------------------------------

        /**
         * When the lesson argument is null the method must fall back to
         * getState('lesson.id') and use that value in the query.
         *
         * @return void
         */
        public function testGetHoursFallsBackToStateWhenLessonIdIsNull(): void
        {
            [$db, $qb] = $this->makeHoursDb([]);
            $model = $this->makeModelWithDbAndState($db, stateLessonId: 17);

            $model->getHours(null);

            $this->assertStringContainsString('17', $qb->capturedWhere);
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Build a fake database whose loadRowList() returns $rows and whose
         * query builder captures the WHERE condition.
         *
         * Returns [$db, $qb] so tests can inspect the captured WHERE.
         *
         * @param   array  $rows  Rows returned by loadRowList().
         *
         * @return  array{0: object, 1: object}
         */
        private function makeHoursDb(array $rows): array
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
            };

            $db = new class ($qb, $rows) {
                public function __construct(
                    private readonly object $qb,
                    private readonly array $rows
                ) {
                }

                public function getQuery(bool $new = true): object
                {
                    return $this->qb;
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        return array_map(static fn($x) => "`$x`", $n);
                    }

                    $name = '`' . $n . '`';

                    if ($a !== null) {
                        return $name . ' AS `' . $a . '`';
                    }

                    return $name;
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function loadRowList(): array
                {
                    return $this->rows;
                }
            };

            return [$db, $qb];
        }

        /**
         * Create an AdminLessonModel stub that uses the provided fake database.
         *
         * @param   object  $db  Fake database instance.
         *
         * @return  AdminLessonModel
         */
        private function makeModelWithDb(object $db): AdminLessonModel
        {
            return new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }
            };
        }

        /**
         * Create an AdminLessonModel stub with an injected DB and a fixed
         * getState('lesson.id') return value.
         *
         * @param   object  $db            Fake database instance.
         * @param   int     $stateLessonId Value returned by getState('lesson.id').
         *
         * @return  AdminLessonModel
         */
        private function makeModelWithDbAndState(object $db, int $stateLessonId): AdminLessonModel
        {
            return new class ($db, $stateLessonId) extends AdminLessonModel {
                public function __construct(
                    private readonly object $db,
                    private readonly int $stateLessonId
                ) {
                }

                public function getState($property = null, $default = null): mixed
                {
                    return $property === 'lesson.id' ? $this->stateLessonId : $default;
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }
            };
        }
    }
}
