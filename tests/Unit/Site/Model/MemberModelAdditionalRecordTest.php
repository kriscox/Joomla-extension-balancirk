<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required by MemberModel::hasAdditionalRecord()
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
    use CoCoCo\Component\Balancirk\Site\Model\MemberModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for MemberModel::hasAdditionalRecord() and ensureAdditionalRecord().
     *
     * These methods were introduced in v1.3.17 to fix a FK error (MySQL 1452)
     * when creating students whose parent lacked a members_additional row.
     *
     * @since  1.3.17
     */
    class MemberModelAdditionalRecordTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // hasAdditionalRecord() — guard clause
        // -----------------------------------------------------------------------

        /**
         * userId=0 must return false without touching the database.
         *
         * @return void
         */
        public function testHasAdditionalRecordReturnsFalseForZeroUserId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();
            $this->assertFalse($model->hasAdditionalRecord(0));
        }

        /**
         * Negative userId must return false without touching the database.
         *
         * @return void
         */
        public function testHasAdditionalRecordReturnsFalseForNegativeUserId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();
            $this->assertFalse($model->hasAdditionalRecord(-1));
        }

        // -----------------------------------------------------------------------
        // hasAdditionalRecord() — DB-backed results
        // -----------------------------------------------------------------------

        /**
         * When loadResult() returns a truthy value the method must return true.
         *
         * @return void
         */
        public function testHasAdditionalRecordReturnsTrueWhenRowExists(): void
        {
            $model = $this->makeModelWithLoadResult(1);
            $this->assertTrue($model->hasAdditionalRecord(5));
        }

        /**
         * When loadResult() returns null (no row) the method must return false.
         *
         * @return void
         */
        public function testHasAdditionalRecordReturnsFalseWhenRowAbsent(): void
        {
            $model = $this->makeModelWithLoadResult(null);
            $this->assertFalse($model->hasAdditionalRecord(5));
        }

        // -----------------------------------------------------------------------
        // ensureAdditionalRecord() — guard clause
        // -----------------------------------------------------------------------

        /**
         * userId=0 must return false without touching the database.
         *
         * @return void
         */
        public function testEnsureAdditionalRecordReturnsFalseForZeroUserId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();
            $this->assertFalse($model->ensureAdditionalRecord(0));
        }

        /**
         * Negative userId must return false without touching the database.
         *
         * @return void
         */
        public function testEnsureAdditionalRecordReturnsFalseForNegativeUserId(): void
        {
            $model = $this->makeModelThatMustNotQueryDb();
            $this->assertFalse($model->ensureAdditionalRecord(-5));
        }

        // -----------------------------------------------------------------------
        // ensureAdditionalRecord() — record already present
        // -----------------------------------------------------------------------

        /**
         * When the row already exists ensureAdditionalRecord() must return true
         * and must not call saveToTable() (which would require a DB write).
         *
         * @return void
         */
        public function testEnsureAdditionalRecordReturnsTrueWhenRowAlreadyExists(): void
        {
            $saveToTableCalled = false;

            $model = new class ($saveToTableCalled) extends MemberModel {
                public function __construct(private bool &$saveToTableCalled)
                {
                }
                public function getDatabase(): never
                {
                    throw new \LogicException('DB must not be queried when record already exists');
                }
                public function hasAdditionalRecord(int $userId): bool
                {
                    return true;
                }
                public function saveToTable(int $id, array $data, bool $insert = false): void
                {
                    $this->saveToTableCalled = true;
                }
            };

            $result = $model->ensureAdditionalRecord(42);

            $this->assertTrue($result);
            $this->assertFalse($saveToTableCalled, 'saveToTable() must not be called when row already exists');
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Create a model subclass that throws if getDatabase() is ever called.
         *
         * @return MemberModel
         */
        private function makeModelThatMustNotQueryDb(): MemberModel
        {
            return new class extends MemberModel {
                public function __construct()
                {
                }
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for this input');
                }
            };
        }

        /**
         * Create a model subclass whose DB stub returns a fixed loadResult() value.
         *
         * @param   mixed  $loadResultValue  Value returned by the fake DB.
         *
         * @return  MemberModel
         */
        private function makeModelWithLoadResult(mixed $loadResultValue): MemberModel
        {
            $qb = new class ($loadResultValue) {
                public function __construct(private readonly mixed $result)
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
                    return $this;
                }
                public function bind(mixed $col, mixed $val, mixed $type = null): static
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

            return new class ($db) extends MemberModel {
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
