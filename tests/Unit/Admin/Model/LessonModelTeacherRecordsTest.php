<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace Joomla\CMS\MVC\Model {
    if (!class_exists('Joomla\\CMS\\MVC\\Model\\AdminModel')) {
        abstract class AdminModel
        {
            public function setError(string $error): void
            {
            }

            public function getError(): string
            {
                return '';
            }

            public function getState($property = null, $default = null): mixed
            {
                return $default;
            }
        }
    }
}

namespace Joomla\CMS\Language {
    if (!class_exists('Joomla\\CMS\\Language\\Text')) {
        class Text
        {
            public static function _($string): string
            {
                return $string;
            }

            public static function sprintf($string, mixed ...$args): string
            {
                return $string;
            }
        }
    }
}

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\LessonModel as AdminLessonModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for AdminLessonModel teacher-record methods introduced in 1.3.18/1.3.20.
     *
     * Covers: getTeacherIdsForLesson(), countTeachedRecords(), hasTeachedRecords(),
     * and the incremental diff logic of saveTeachers().
     *
     * @since  1.3.20
     */
    class LessonModelTeacherRecordsTest extends TestCase
    {
        // -------------------------------------------------------------------------
        // getTeacherIdsForLesson() — guard clauses
        // -------------------------------------------------------------------------

        /**
         * getTeacherIdsForLesson() with zero lessonId must return empty without DB access.
         *
         * @return void
         */
        public function testGetTeacherIdsForLessonReturnsEmptyForZeroLessonId(): void
        {
            $model = $this->makeGuardModel();

            $this->assertSame([], $model->getTeacherIdsForLesson(0));
        }

        /**
         * getTeacherIdsForLesson() with negative lessonId must return empty without DB access.
         *
         * @return void
         */
        public function testGetTeacherIdsForLessonReturnsEmptyForNegativeLessonId(): void
        {
            $model = $this->makeGuardModel();

            $this->assertSame([], $model->getTeacherIdsForLesson(-5));
        }

        /**
         * getTeacherIdsForLesson() with a valid lessonId maps string column values to ints.
         *
         * @return void
         */
        public function testGetTeacherIdsForLessonMapsColumnToIntegers(): void
        {
            $capturedWhere = '';
            [$db] = $this->makeQueryDb(
                loadColumnResult: ['1', '3', '7'],
                capturedWhere: $capturedWhere
            );

            $model = $this->makeModelWithDb($db);

            $result = $model->getTeacherIdsForLesson(5);

            $this->assertSame([1, 3, 7], $result);
            $this->assertStringContainsString('5', $capturedWhere);
        }

        // -------------------------------------------------------------------------
        // countTeachedRecords() — guard clauses
        // -------------------------------------------------------------------------

        /**
         * countTeachedRecords() with zero memberId must return 0 without DB access.
         *
         * @return void
         */
        public function testCountTeachedRecordsReturnsZeroForZeroMemberId(): void
        {
            $model = $this->makeGuardModel();

            $this->assertSame(0, $model->countTeachedRecords(0));
        }

        /**
         * countTeachedRecords() with negative memberId must return 0 without DB access.
         *
         * @return void
         */
        public function testCountTeachedRecordsReturnsZeroForNegativeMemberId(): void
        {
            $model = $this->makeGuardModel();

            $this->assertSame(0, $model->countTeachedRecords(-3, 5));
        }

        // -------------------------------------------------------------------------
        // countTeachedRecords() — WHERE clause composition
        // -------------------------------------------------------------------------

        /**
         * countTeachedRecords() without lessonId must not add a lesson WHERE clause.
         *
         * @return void
         */
        public function testCountTeachedRecordsWithoutLessonIdOmitsLessonFilter(): void
        {
            $whereClauses = [];
            [$db] = $this->makeCountDb(count: 4, whereClauses: $whereClauses);

            $model = $this->makeModelWithDb($db);
            $result = $model->countTeachedRecords(3);

            $this->assertSame(4, $result);
            // Only the teacher WHERE clause should be present; no lesson filter.
            $this->assertCount(1, $whereClauses);
            $this->assertStringContainsString('3', $whereClauses[0]);
        }

        /**
         * countTeachedRecords() with a lessonId must add a lesson WHERE clause.
         *
         * @return void
         */
        public function testCountTeachedRecordsWithLessonIdAddsLessonFilter(): void
        {
            $whereClauses = [];
            [$db] = $this->makeCountDb(count: 2, whereClauses: $whereClauses);

            $model = $this->makeModelWithDb($db);
            $result = $model->countTeachedRecords(3, 7);

            $this->assertSame(2, $result);
            $this->assertCount(2, $whereClauses);
            $this->assertStringContainsString('3', $whereClauses[0]);
            $this->assertStringContainsString('7', $whereClauses[1]);
        }

        // -------------------------------------------------------------------------
        // hasTeachedRecords()
        // -------------------------------------------------------------------------

        /**
         * hasTeachedRecords() must return false for zero memberId (guard, no DB).
         *
         * @return void
         */
        public function testHasTeachedRecordsReturnsFalseForZeroMemberId(): void
        {
            $model = $this->makeGuardModel();

            $this->assertFalse($model->hasTeachedRecords(0));
        }

        /**
         * hasTeachedRecords() must return true when DB count is greater than zero.
         *
         * @return void
         */
        public function testHasTeachedRecordsTrueWhenCountNonZero(): void
        {
            $whereClauses = [];
            [$db] = $this->makeCountDb(count: 3, whereClauses: $whereClauses);

            $model = $this->makeModelWithDb($db);

            $this->assertTrue($model->hasTeachedRecords(5, 2));
        }

        /**
         * hasTeachedRecords() must return false when DB count is zero.
         *
         * @return void
         */
        public function testHasTeachedRecordsFalseWhenCountZero(): void
        {
            $whereClauses = [];
            [$db] = $this->makeCountDb(count: 0, whereClauses: $whereClauses);

            $model = $this->makeModelWithDb($db);

            $this->assertFalse($model->hasTeachedRecords(5, 2));
        }

        // -------------------------------------------------------------------------
        // saveTeachers() — incremental diff logic
        // -------------------------------------------------------------------------

        /**
         * saveTeachers() must only insert newly added teachers, not existing ones.
         *
         * @return void
         */
        public function testSaveTeachersOnlyInsertsNewTeachers(): void
        {
            $ops = [];
            $db = $this->makeSaveTeachersDb($ops);

            $model = new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [1, 2];
                }

                public function countTeachedRecords(int $memberId, ?int $lessonId = null): int
                {
                    return 0;
                }
            };

            // Current [1, 2], requested [2, 3] → insert 3, delete 1
            $result = $model->saveTeachers(5, [2, 3]);

            $this->assertTrue($result);
            $insertedMembers = array_column(
                array_filter($ops, static fn($o) => $o['type'] === 'INSERT'),
                'member'
            );
            $this->assertSame([3], array_values($insertedMembers), 'Only member 3 should be inserted');
        }

        /**
         * saveTeachers() must only delete removed teachers, not retained ones.
         *
         * @return void
         */
        public function testSaveTeachersOnlyDeletesRemovedTeachers(): void
        {
            $ops = [];
            $db = $this->makeSaveTeachersDb($ops);

            $model = new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [1, 2];
                }

                public function countTeachedRecords(int $memberId, ?int $lessonId = null): int
                {
                    return 0;
                }
            };

            // Current [1, 2], requested [2] → delete 1 only
            $model->saveTeachers(5, [2]);

            $deletedMembers = array_column(
                array_filter($ops, static fn($o) => $o['type'] === 'DELETE'),
                'member'
            );
            $this->assertSame([1], array_values($deletedMembers), 'Only member 1 should be deleted');
        }

        /**
         * saveTeachers() must not issue any DB writes when teachers are unchanged.
         *
         * @return void
         */
        public function testSaveTeachersSkipsUnchangedTeachers(): void
        {
            $ops = [];
            $db = $this->makeSaveTeachersDb($ops);

            $model = new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [1, 2];
                }

                public function countTeachedRecords(int $memberId, ?int $lessonId = null): int
                {
                    return 0;
                }
            };

            // Current [1, 2], requested [1, 2] → no changes
            $model->saveTeachers(5, [1, 2]);

            $this->assertEmpty($ops, 'No DB writes should occur when teacher set is unchanged');
        }

        /**
         * saveTeachers() must return false and skip DB writes when a teacher with
         * attendance records cannot be unassigned.
         *
         * @return void
         */
        public function testSaveTeachersReturnsFalseWhenRemovalIsBlocked(): void
        {
            $ops = [];
            $db = $this->makeSaveTeachersDb($ops);

            $model = new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [1];
                }

                public function countTeachedRecords(int $memberId, ?int $lessonId = null): int
                {
                    return ($memberId === 1) ? 3 : 0;
                }
            };

            // Current [1], requested [] → try to remove 1, but has 3 attendance records
            $result = $model->saveTeachers(5, []);

            $this->assertFalse($result);
            $this->assertEmpty($ops, 'No DB writes should occur when removal is blocked');
        }

        // -------------------------------------------------------------------------
        // Helpers
        // -------------------------------------------------------------------------

        /**
         * Create a model stub without a database, useful for guard-clause tests.
         *
         * @return AdminLessonModel
         */
        private function makeGuardModel(): AdminLessonModel
        {
            return new class extends AdminLessonModel {
            };
        }

        /**
         * Create a model stub that delegates getDatabase() to the given fake DB.
         *
         * @param   object  $db  Fake database.
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
         * Build a fake DB whose query builder captures WHERE clauses and returns a
         * fixed loadColumn() result.
         *
         * @param   array   $loadColumnResult  Values returned by loadColumn().
         * @param   string  &$capturedWhere    Receives the last WHERE clause string.
         *
         * @return  array{0: object}
         */
        private function makeQueryDb(array $loadColumnResult, string &$capturedWhere): array
        {
            $qb = new class ($capturedWhere, $loadColumnResult) {
                public function __construct(
                    private string &$capturedWhere,
                    private readonly array $loadColumnResult
                ) {
                }

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

                public function getLoadColumnResult(): array
                {
                    return $this->loadColumnResult;
                }
            };

            $db = new class ($qb) {
                public function __construct(private readonly object $qb)
                {
                }

                public function getQuery(bool $new): object
                {
                    return $this->qb;
                }

                public function quoteName(mixed $n): string
                {
                    return "`$n`";
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function loadColumn(): array
                {
                    return $this->qb->getLoadColumnResult();
                }
            };

            return [$db];
        }

        /**
         * Build a fake DB whose query builder captures multiple WHERE clauses and
         * returns a fixed loadResult() count.
         *
         * @param   int    $count        Value returned by loadResult().
         * @param   array  &$whereClauses  Receives accumulated WHERE clause strings.
         *
         * @return  array{0: object}
         */
        private function makeCountDb(int $count, array &$whereClauses): array
        {
            $qb = new class ($count, $whereClauses) {
                public function __construct(
                    private readonly int $count,
                    private array &$whereClauses
                ) {
                }

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
                    $this->whereClauses[] = (string) $cond;

                    return $this;
                }

                public function getCount(): int
                {
                    return $this->count;
                }
            };

            $db = new class ($qb) {
                public function __construct(private readonly object $qb)
                {
                }

                public function getQuery(bool $new): object
                {
                    return $this->qb;
                }

                public function quoteName(mixed $n): string
                {
                    return "`$n`";
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function loadResult(): int
                {
                    return $this->qb->getCount();
                }
            };

            return [$db];
        }

        /**
         * Build a fake DB that tracks INSERT and DELETE operations for saveTeachers() tests.
         *
         * Each tracked operation is an array: ['type' => 'INSERT'|'DELETE', 'member' => int].
         *
         * @param   array  &$ops  Receives tracked operation records.
         *
         * @return  object
         */
        private function makeSaveTeachersDb(array &$ops): object
        {
            return new class ($ops) {
                private string $opType = '';
                private int $lastMember = 0;

                public function __construct(private array &$ops)
                {
                }

                public function getQuery(bool $new): object
                {
                    $owner = $this;

                    return new class ($owner) {
                        public function __construct(private readonly object $owner)
                        {
                        }

                        public function insert(string $t): static
                        {
                            $this->owner->setOpType('INSERT');

                            return $this;
                        }

                        public function delete(string $t): static
                        {
                            $this->owner->setOpType('DELETE');

                            return $this;
                        }

                        public function columns(mixed $c): static
                        {
                            return $this;
                        }

                        public function values(string $v): static
                        {
                            // Parse the first integer as member id from "memberId, lessonId"
                            if (preg_match('/^(\d+)/', $v, $m)) {
                                $this->owner->setLastMember((int) $m[1]);
                            }

                            return $this;
                        }

                        public function where(mixed $cond): static
                        {
                            // Capture member id from WHERE clause like "`member` = 1"
                            if (preg_match('/member.*?(\d+)/', (string) $cond, $m)) {
                                $this->owner->setLastMember((int) $m[1]);
                            }

                            return $this;
                        }
                    };
                }

                public function quoteName(mixed $n): string
                {
                    if (is_array($n)) {
                        return implode(', ', array_map(static fn($x) => "`$x`", $n));
                    }

                    return "`$n`";
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function execute(): void
                {
                    $this->ops[] = ['type' => $this->opType, 'member' => $this->lastMember];
                    $this->opType = '';
                    $this->lastMember = 0;
                }

                public function setOpType(string $type): void
                {
                    $this->opType = $type;
                }

                public function setLastMember(int $id): void
                {
                    $this->lastMember = $id;
                }
            };
        }
    }
}
