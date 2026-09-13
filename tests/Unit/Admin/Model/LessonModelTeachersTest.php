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
     * Tests for AdminLessonModel teacher lookup helpers.
     *
     * getTeachers() displays assigned teachers in the lesson edit view.
     * getAvailableTeachers() populates the selection list.
     *
     * Incremental saveTeachers() behaviour (diff inserts/deletes and attendance
     * blocking) is covered by LessonModelTeacherRecordsTest after 1.3.18/1.3.20.
     *
     * Key regression risks:
     *  - getTeachers() with no lesson id must return [] without a DB hit
     *    (avoids querying with id = 0 which could return unexpected rows).
     *  - getAvailableTeachers() must short-circuit if the Teachers group
     *    does not exist (avoids a query with groupId = 0).
     *
     * @since  1.3.22
     */
    class LessonModelTeachersTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // getTeachers() — guard clause
        // -----------------------------------------------------------------------

        /**
         * getTeachers(null) when getState() returns 0 must return [] without
         * querying the database.
         *
         * @return void
         */
        public function testGetTeachersReturnsEmptyWhenNoLessonIdAndNoState(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertSame([], $model->getTeachers(null));
        }

        /**
         * getTeachers(0) must return [] without querying the database.
         *
         * @return void
         */
        public function testGetTeachersReturnsEmptyForZeroLessonId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertSame([], $model->getTeachers(0));
        }

        /**
         * getTeachers() with a valid lesson id must return whatever loadObjectList() provides.
         *
         * @return void
         */
        public function testGetTeachersReturnsLoadObjectListResultForValidId(): void
        {
            $expected = [
                (object)['id' => 1, 'firstname' => 'Jan', 'name' => 'Janssen', 'email' => 'j@example.com'],
            ];

            $db = $this->makeFullFakeDb(loadObjectResult: $expected);
            $model = $this->makeModelWithDb($db);

            $result = $model->getTeachers(5);

            $this->assertSame($expected, $result);
        }

        /**
         * getTeachers() must return an empty array when no teachers are assigned.
         *
         * @return void
         */
        public function testGetTeachersReturnsEmptyArrayWhenNoTeachersAssigned(): void
        {
            $db = $this->makeFullFakeDb(loadObjectResult: []);
            $model = $this->makeModelWithDb($db);

            $this->assertSame([], $model->getTeachers(3));
        }

        // -----------------------------------------------------------------------
        // getAvailableTeachers() — Teachers group guard
        // -----------------------------------------------------------------------

        /**
         * When no Teachers group exists in the DB, getAvailableTeachers() must
         * return [] without issuing the member query.
         *
         * @return void
         */
        public function testGetAvailableTeachersReturnsEmptyWhenNoTeachersGroup(): void
        {
            $db = $this->makeFullFakeDb(loadResult: null, loadObjectResult: []);
            $model = $this->makeModelWithDb($db);

            $this->assertSame([], $model->getAvailableTeachers());
        }

        /**
         * When the Teachers group exists, getAvailableTeachers() must return
         * whatever loadObjectList() provides.
         *
         * @return void
         */
        public function testGetAvailableTeachersReturnsMembersWhenGroupExists(): void
        {
            $expected = [
                (object)['id' => 2, 'firstname' => 'Piet', 'name' => 'Pietersen', 'email' => 'p@example.com'],
            ];

            $db = $this->makeFullFakeDb(loadResult: 7, loadObjectResult: $expected);
            $model = $this->makeModelWithDb($db);

            $result = $model->getAvailableTeachers();

            $this->assertSame($expected, $result);
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Create an AdminLessonModel subclass that throws if getDatabase() is called.
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
                    return null;
                }
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for this input');
                }
            };
        }

        /**
         * Create a fully-functional fake database for getTeachers() and getAvailableTeachers().
         *
         * The fake DB returns $loadResult from loadResult() (used for the group-id lookup)
         * and $loadObjectResult from loadObjectList() (used for the member/teacher rows).
         *
         * @param   mixed  $loadResult        Value returned by loadResult().
         * @param   array  $loadObjectResult  Value returned by loadObjectList().
         *
         * @return  object
         */
        private function makeFullFakeDb(mixed $loadResult = null, array $loadObjectResult = []): object
        {
            $qb = new class {
                public function select(mixed $x): static
                {
                    return $this;
                }
                public function from(mixed $t): static
                {
                    return $this;
                }
                public function join(mixed $type, mixed $cond, mixed $on = null): static
                {
                    return $this;
                }
                public function where(mixed $cond): static
                {
                    return $this;
                }
                public function order(mixed $o): static
                {
                    return $this;
                }
            };

            return new class ($qb, $loadResult, $loadObjectResult) {
                public function __construct(
                    private readonly object $qb,
                    private readonly mixed $loadResult,
                    private readonly array $loadObjectResult
                ) {
                }
                public function getQuery(bool $new): object
                {
                    return $this->qb;
                }
                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        return array_map(static fn($x) => "`$x`", $n);
                    }
                    return "`$n`";
                }
                public function quote(mixed $value): string
                {
                    return "'$value'";
                }
                public function setQuery(mixed $q): static
                {
                    return $this;
                }
                public function loadResult(): mixed
                {
                    return $this->loadResult;
                }
                public function loadObjectList(): array
                {
                    return $this->loadObjectResult;
                }
            };
        }

        /**
         * Create an AdminLessonModel subclass that injects a custom fake database.
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
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function getDatabase(): object
                {
                    return $this->db;
                }
            };
        }
    }
}
