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
    }
}
