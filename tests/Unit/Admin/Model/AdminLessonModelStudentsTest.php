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
// NOTE: This file loads before AdminLessonModelValidateTest.php (S < V).
// The AdminModel stub therefore MUST include validate() so that the validate
// test can call parent::validate() when it reuses this already-defined stub.
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
     * Tests for AdminLessonModel::getStudents() and getWaitingListStudents().
     *
     * Both methods delegate to the private getSubscribedStudents() helper.
     * Key regression risks:
     *  - When no lesson id is resolvable from the argument, state, or item,
     *    the method must return [] without issuing a DB query.
     *  - getStudents() must pass subscribed=0 (enrolled) to the JOIN.
     *  - getWaitingListStudents() must pass subscribed=1 (waiting list) to the JOIN.
     *  - The DB result is returned as-is; an empty DB result becomes [].
     *
     * @since  1.3.21
     */
    class AdminLessonModelStudentsTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // Guard clause — no lesson id resolved
        // -----------------------------------------------------------------------

        /**
         * getStudents(null) when getState and getItem both resolve to 0 must return []
         * without querying the database.
         *
         * @return void
         */
        public function testGetStudentsReturnsEmptyWhenNoLessonIdCanBeResolved(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertSame([], $model->getStudents(null));
        }

        /**
         * getWaitingListStudents(null) under the same conditions must also return [].
         *
         * @return void
         */
        public function testGetWaitingListStudentsReturnsEmptyWhenNoLessonIdCanBeResolved(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertSame([], $model->getWaitingListStudents(null));
        }

        /**
         * Passing an explicit lessonId of 0 resolves to no lesson → [].
         *
         * @return void
         */
        public function testGetStudentsReturnsEmptyForExplicitZeroLessonId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertSame([], $model->getStudents(0));
        }

        // -----------------------------------------------------------------------
        // getStudents() — enrolled students (subscribed = 0)
        // -----------------------------------------------------------------------

        /**
         * getStudents() with a valid lesson id must return whatever loadObjectList()
         * provides.
         *
         * @return void
         */
        public function testGetStudentsReturnsDbRowsForValidLessonId(): void
        {
            $expected = [
                (object)['id' => 3, 'name' => 'Baert', 'firstname' => 'Lena', 'birthdate' => '2015-06-01',
                    'subscription_id' => 11],
            ];

            $db = $this->makeStudentsDb($expected);
            $model = $this->makeModelWithDb($db);

            $result = $model->getStudents(7);

            $this->assertSame($expected, $result);
        }

        /**
         * getStudents() must return [] (not false or null) when no enrolled students
         * are found.
         *
         * @return void
         */
        public function testGetStudentsReturnsEmptyArrayWhenDbReturnsEmpty(): void
        {
            $db = $this->makeStudentsDb([]);
            $model = $this->makeModelWithDb($db);

            $this->assertSame([], $model->getStudents(7));
        }

        /**
         * getStudents() must use subscribed = 0 in the JOIN condition so only
         * enrolled students are retrieved.
         *
         * @return void
         */
        public function testGetStudentsFiltersBySubscribedZero(): void
        {
            $db = $this->makeStudentsDb([]);
            $model = $this->makeModelWithDb($db);

            $model->getStudents(5);

            $this->assertStringContainsString(
                's.subscribed = 0',
                $db->qb->lastJoinCond,
                'getStudents() must join on subscribed = 0 (enrolled)'
            );
        }

        // -----------------------------------------------------------------------
        // getWaitingListStudents() — waiting-list students (subscribed = 1)
        // -----------------------------------------------------------------------

        /**
         * getWaitingListStudents() must return whatever loadObjectList() provides.
         *
         * @return void
         */
        public function testGetWaitingListStudentsReturnsDbRows(): void
        {
            $expected = [
                (object)['id' => 9, 'name' => 'Claes', 'firstname' => 'Tom', 'birthdate' => '2014-03-15',
                    'subscription_id' => 22],
            ];

            $db = $this->makeStudentsDb($expected);
            $model = $this->makeModelWithDb($db);

            $result = $model->getWaitingListStudents(4);

            $this->assertSame($expected, $result);
        }

        /**
         * getWaitingListStudents() must use subscribed = 1 in the JOIN condition so
         * only waiting-list students are retrieved.
         *
         * @return void
         */
        public function testGetWaitingListStudentsFiltersBySubscribedOne(): void
        {
            $db = $this->makeStudentsDb([]);
            $model = $this->makeModelWithDb($db);

            $model->getWaitingListStudents(5);

            $this->assertStringContainsString(
                's.subscribed = 1',
                $db->qb->lastJoinCond,
                'getWaitingListStudents() must join on subscribed = 1 (waiting list)'
            );
        }

        // -----------------------------------------------------------------------
        // lessonId resolution — getState() fallback
        // -----------------------------------------------------------------------

        /**
         * When lessonId is not passed but getState('lesson.id') returns a non-zero
         * value, getStudents() must run the DB query with that lesson id.
         *
         * @return void
         */
        public function testGetStudentsUsesStateWhenNoExplicitLessonId(): void
        {
            $expected = [
                (object)['id' => 1, 'name' => 'A', 'firstname' => 'B', 'birthdate' => '2010-01-01',
                    'subscription_id' => 5],
            ];

            $db = $this->makeStudentsDb($expected);
            $model = $this->makeModelWithDbAndState($db, stateLessonId: 8);

            $result = $model->getStudents();

            $this->assertSame($expected, $result);
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Create an AdminLessonModel stub that throws if getDatabase() is called.
         * getState() returns 0 and getItem() returns an object with id = 0.
         *
         * @return AdminLessonModel
         */
        private function makeModelThatMustNotQueryDb(): AdminLessonModel
        {
            return new class extends AdminLessonModel {
                public function __construct()
                {
                }

                public function getState($property = null, $default = null): mixed
                {
                    return 0;
                }

                public function getItem($pk = null): object
                {
                    return (object)['id' => 0];
                }

                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for this input');
                }
            };
        }

        /**
         * Create an AdminLessonModel stub with an injected fake database.
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
         * Create an AdminLessonModel stub with an injected DB and a fixed getState value.
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

        /**
         * Build a fake database that captures the JOIN condition and returns $rows
         * from loadObjectList().
         *
         * The query builder exposes the last JOIN condition via $db->qb->lastJoinCond
         * so tests can assert on the subscribed filter value.
         *
         * @param   array  $rows  Rows returned by loadObjectList().
         *
         * @return  object
         */
        private function makeStudentsDb(array $rows): object
        {
            $qb = new class {
                public string $lastJoinCond = '';

                public function select(mixed $x): static
                {
                    return $this;
                }

                public function from(mixed $t): static
                {
                    return $this;
                }

                public function join(mixed $type, mixed $cond): static
                {
                    $this->lastJoinCond = (string) $cond;

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

            return new class ($qb, $rows) {
                public function __construct(
                    public readonly object $qb,
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
                        return array_map(static fn($x) => '`' . $x . '`', $n);
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

                public function loadObjectList(): array
                {
                    return $this->rows;
                }
            };
        }
    }
}
