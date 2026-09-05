<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required for loading StudentModel.
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

namespace Joomla\Database {
    if (!class_exists('Joomla\\Database\\ParameterType')) {
        class ParameterType
        {
            public const INTEGER = 0;
            public const STRING  = 1;
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model {
    use CoCoCo\Component\Balancirk\Site\Model\StudentModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for StudentModel::hasCurrentYearSubscription().
     *
     * hasCurrentYearSubscription() was introduced in v1.3.x to guard the
     * student-deletion flow: a student with an active subscription for the
     * current school year must not be deleted.
     *
     * Key regression risks:
     *  - A zero or negative student id must return false immediately without
     *    touching the database.  Querying with id ≤ 0 could accidentally match
     *    unintended rows or cause unexpected DB behaviour.
     *  - A null argument should resolve via getState(); if state also returns 0
     *    the guard must still return false without a DB hit.
     *  - When the DB reports a non-zero count the method must return true;
     *    a zero count must return false.
     *
     * @since  1.3.x
     */
    class StudentModelTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // hasCurrentYearSubscription() — guard clauses (no DB hit)
        // -----------------------------------------------------------------------

        /**
         * studentId = 0 must return false without querying the database.
         *
         * @return void
         */
        public function testHasCurrentYearSubscriptionReturnsFalseForZeroId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertFalse($model->hasCurrentYearSubscription(0));
        }

        /**
         * Negative studentId must return false without querying the database.
         *
         * @return void
         */
        public function testHasCurrentYearSubscriptionReturnsFalseForNegativeId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();

            $this->assertFalse($model->hasCurrentYearSubscription(-1));
        }

        /**
         * Null argument with a state('student.id') of 0 must return false
         * without querying the database.
         *
         * @return void
         */
        public function testHasCurrentYearSubscriptionReturnsFalseWhenNullAndStateIsZero(): void
        {
            $model = new class extends StudentModel {
                public function __construct()
                {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return 0;
                }
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called when state.id is 0');
                }
            };

            $this->assertFalse($model->hasCurrentYearSubscription(null));
        }

        // -----------------------------------------------------------------------
        // hasCurrentYearSubscription() — DB-backed results
        // -----------------------------------------------------------------------

        /**
         * When loadResult() returns a positive count the method must return true.
         *
         * @return void
         */
        public function testHasCurrentYearSubscriptionReturnsTrueWhenCountIsPositive(): void
        {
            $model = $this->makeModelWithLoadResult(2);

            $this->assertTrue($model->hasCurrentYearSubscription(5));
        }

        /**
         * When loadResult() returns 0 the method must return false.
         *
         * @return void
         */
        public function testHasCurrentYearSubscriptionReturnsFalseWhenCountIsZero(): void
        {
            $model = $this->makeModelWithLoadResult(0);

            $this->assertFalse($model->hasCurrentYearSubscription(5));
        }

        /**
         * When loadResult() returns '0' (string) the method must return false.
         *
         * @return void
         */
        public function testHasCurrentYearSubscriptionReturnsFalseForStringZero(): void
        {
            $model = $this->makeModelWithLoadResult('0');

            $this->assertFalse($model->hasCurrentYearSubscription(3));
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Create a StudentModel subclass that throws if getDatabase() is called.
         *
         * getState() returns 0 so that the `$studentId ?: (int) $this->getState('student.id')`
         * fallback in hasCurrentYearSubscription() keeps the effective id at 0,
         * which triggers the guard immediately without a DB call.
         *
         * @return StudentModel
         */
        private function makeModelThatMustNotQueryDb(): StudentModel
        {
            return new class extends StudentModel {
                public function __construct()
                {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return 0;
                }
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for this input');
                }
            };
        }

        /**
         * Create a StudentModel subclass whose DB stub returns a fixed loadResult() value.
         *
         * The query builder accepts select(), from(), join(), where(), and bind()
         * without doing anything real — we only care about the final loadResult() call.
         *
         * @param   mixed  $loadResultValue  Value returned by the fake DB.
         *
         * @return  StudentModel
         */
        private function makeModelWithLoadResult(mixed $loadResultValue): StudentModel
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
                public function join(mixed $type, mixed $cond): static
                {
                    return $this;
                }
                public function where(mixed $c): static
                {
                    return $this;
                }
                public function bind(mixed $name, mixed &$value, mixed $type = null): static
                {
                    return $this;
                }
            };

            $db = new class ($qb, $loadResultValue) {
                public function __construct(
                    private readonly object $qb,
                    private readonly mixed $loadResultValue
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
                public function setQuery(mixed $q): static
                {
                    return $this;
                }
                public function loadResult(): mixed
                {
                    return $this->loadResultValue;
                }
            };

            return new class ($db) extends StudentModel {
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

        // -----------------------------------------------------------------------
        // isPrimairyParent() — DB-backed access control check
        // -----------------------------------------------------------------------

        /**
         * When execute() succeeds and getNumRows() >= 1 the method must return true.
         *
         * isPrimairyParent() is the gatekeeper for student-record edits: a non-primary
         * parent must not be able to modify a student's data.
         *
         * @return void
         */
        public function testIsPrimairyParentReturnsTrueWhenExecuteSucceedsAndHasRows(): void
        {
            $model = $this->makeIsPrimairyParentModel(true, 2);

            $this->assertTrue($model->isPrimairyParent(5, 10));
        }

        /**
         * When execute() succeeds but getNumRows() == 0 the method must return false.
         *
         * @return void
         */
        public function testIsPrimairyParentReturnsFalseWhenExecuteSucceedsButNoRows(): void
        {
            $model = $this->makeIsPrimairyParentModel(true, 0);

            $this->assertFalse($model->isPrimairyParent(5, 10));
        }

        /**
         * When execute() returns false (DB error) the method must return false.
         *
         * @return void
         */
        public function testIsPrimairyParentReturnsFalseWhenExecuteFails(): void
        {
            $model = $this->makeIsPrimairyParentModel(false, 0);

            $this->assertFalse($model->isPrimairyParent(5, 10));
        }

        // -----------------------------------------------------------------------
        // getParents() — primary filter flag
        // -----------------------------------------------------------------------

        /**
         * With primary=true (default) the WHERE clause must include the primary filter.
         *
         * @return void
         */
        public function testGetParentsWithPrimaryTrueAddsPrimaryFilter(): void
        {
            $capturedWhere = [];

            $model = $this->makeGetParentsModel($capturedWhere, []);
            $model->getParents(7, true);

            $combined = implode(' ', $capturedWhere);
            $this->assertStringContainsString('primary', $combined);
        }

        /**
         * With primary=false the WHERE clause must NOT include the primary filter.
         *
         * @return void
         */
        public function testGetParentsWithPrimaryFalseOmitsPrimaryFilter(): void
        {
            $capturedWhere = [];

            $model = $this->makeGetParentsModel($capturedWhere, []);
            $model->getParents(7, false);

            $combined = implode(' ', $capturedWhere);
            $this->assertStringNotContainsString('primary', $combined);
        }

        /**
         * getParents() must return whatever loadObjectList() returns.
         *
         * @return void
         */
        public function testGetParentsReturnsLoadObjectListResult(): void
        {
            $expected = [(object)['parent' => 3], (object)['parent' => 9]];
            $capturedWhere = [];

            $model = $this->makeGetParentsModel($capturedWhere, $expected);

            $this->assertSame($expected, $model->getParents(7));
        }

        // -----------------------------------------------------------------------
        // Helpers for isPrimairyParent() and getParents()
        // -----------------------------------------------------------------------

        /**
         * Build a StudentModel whose DB stub simulates isPrimairyParent() behaviour.
         *
         * @param   bool  $executeResult  Value returned by execute().
         * @param   int   $numRows        Value returned by getNumRows().
         *
         * @return  StudentModel
         */
        private function makeIsPrimairyParentModel(bool $executeResult, int $numRows): StudentModel
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
                public function where(mixed $c): static
                {
                    return $this;
                }
            };

            $db = new class ($qb, $executeResult, $numRows) {
                public function __construct(
                    private readonly object $qb,
                    private readonly bool   $executeResult,
                    private readonly int    $numRows
                ) {
                }
                public function getQuery(bool $new): object
                {
                    return $this->qb;
                }
                public function quote(mixed $v): string
                {
                    return "'$v'";
                }
                public function quoteName(mixed $n, mixed $a = null): string
                {
                    return "`$n`";
                }
                public function setQuery(mixed $q): static
                {
                    return $this;
                }
                public function execute(): bool
                {
                    return $this->executeResult;
                }
                public function getNumRows(): int
                {
                    return $this->numRows;
                }
            };

            return new class ($db) extends StudentModel {
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
         * Build a StudentModel whose DB stub captures WHERE clauses and returns a
         * fixed loadObjectList() value for getParents().
         *
         * @param   array  $capturedWhere  Reference array populated by the query builder.
         * @param   array  $listResult     Value returned by loadObjectList().
         *
         * @return  StudentModel
         */
        private function makeGetParentsModel(array &$capturedWhere, array $listResult): StudentModel
        {
            $qb = new class ($capturedWhere) {
                public function __construct(private array &$captured)
                {
                }
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
                    $this->captured[] = (string) $c;
                    return $this;
                }
            };

            $db = new class ($qb, $listResult) {
                public function __construct(
                    private readonly object $qb,
                    private readonly array  $listResult
                ) {
                }
                public function getQuery(bool $new): object
                {
                    return $this->qb;
                }
                public function quote(mixed $v): string
                {
                    return "'$v'";
                }
                public function quoteName(mixed $n, mixed $a = null): string
                {
                    return "`$n`";
                }
                public function setQuery(mixed $q): static
                {
                    return $this;
                }
                public function loadObjectList(): array
                {
                    return $this->listResult;
                }
            };

            return new class ($db) extends StudentModel {
                public function __construct(private readonly object $db)
                {
                }
                public function getDatabase(): object
                {
                    return $this->db;
                }
            };
        }
    }
}
