<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla\Database stub — ParameterType is used by hasAdditionalRecord().
// Declared in its own bracketed namespace block so the rest of the file can
// use bracketed blocks too (PHP forbids mixing bracketed / unbracketed forms).
// ---------------------------------------------------------------------------
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
// Test namespace
// ---------------------------------------------------------------------------
namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model {

    use PHPUnit\Framework\TestCase;
    use CoCoCo\Component\Balancirk\Site\Model\MemberModel;

    // AdminModel stub — aliased once per process.
    if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
        class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
    }

    // -----------------------------------------------------------------------
    // DB stubs for saveToTable() and hasAdditionalRecord() tests
    //
    // The recording query builder captures what the model passes to values()
    // and set() so assertions can verify that missing optional fields default
    // to ''.  The loadResult value is configurable for hasAdditionalRecord().
    // -----------------------------------------------------------------------

    /** Records the values string passed to ->values() and fields passed to ->set(). */
    final class MemberQueryBuilder
    {
        public string $capturedValues = '';
        /** @var string[] */
        public array $capturedSetFields = [];

        public function insert(mixed $x): static { return $this; }
        public function columns(mixed $x): static { return $this; }
        public function values(string $v): static { $this->capturedValues = $v; return $this; }
        public function update(mixed $x): static { return $this; }
        public function set(array $fields): static { $this->capturedSetFields = $fields; return $this; }
        public function where(mixed $x): static { return $this; }
        public function select(mixed $x): static { return $this; }
        public function from(mixed $x): static { return $this; }
        public function bind(mixed $column, mixed &$value, mixed $type = null): static { return $this; }
    }

    /**
     * DB stub that wraps a shared MemberQueryBuilder.
     *
     * The loadResult() return value is configurable so tests can simulate
     * both "record found" and "not found" for hasAdditionalRecord().
     */
    final class MemberFakeDatabase
    {
        public function __construct(
            private readonly MemberQueryBuilder $qb,
            private readonly mixed $loadResult = null
        ) {}

        public function getQuery(bool $new = false): MemberQueryBuilder
        {
            return $this->qb;
        }

        public function quoteName(mixed $x): mixed
        {
            if (is_array($x)) {
                return array_map(fn ($v) => "`$v`", $x);
            }
            return "`$x`";
        }

        public function quote(mixed $x): string { return "'$x'"; }

        public function setQuery(object $q): static { return $this; }

        public function execute(): bool { return true; }

        public function loadResult(): mixed { return $this->loadResult; }
    }

    /** Testable subclass that injects our fake DB. */
    final class TestableSiteMemberModel extends MemberModel
    {
        public function __construct(private readonly MemberFakeDatabase $fakeDb) {}

        public function getDatabase(): MemberFakeDatabase
        {
            return $this->fakeDb;
        }
    }

    /**
     * Unit tests for site MemberModel::saveToTable(), hasAdditionalRecord(),
     * and ensureAdditionalRecord() guard clauses.
     *
     * saveToTable() uses null-coalescing defaults ($data[key] ?? '') for every
     * optional column, preventing PHP warnings / incomplete INSERTs when
     * optional registration fields are absent — a real bug that caused FK
     * errors (MySQL 1452) when the API or a minimal registration form omitted
     * fields such as street, bus, or postcode.
     *
     * hasAdditionalRecord() guards against invalid user IDs before touching
     * the database.  ensureAdditionalRecord() relies on this guard to fail
     * fast for unauthenticated callers (userId <= 0).
     *
     * @since  1.3.8
     */
    class MemberModelTest extends TestCase
    {
        private function makeModel(mixed $loadResult = null): array
        {
            $qb = new MemberQueryBuilder();
            $db = new MemberFakeDatabase($qb, $loadResult);
            $model = new TestableSiteMemberModel($db);

            return [$model, $qb, $db];
        }

        // -------------------------------------------------------------------
        // hasAdditionalRecord() — guard clauses (no DB call expected)
        // -------------------------------------------------------------------

        public function testHasAdditionalRecordReturnsFalseWhenUserIdIsZero(): void
        {
            // The guard clause returns false before reaching getDatabase(), so we
            // verify by passing a model whose getDatabase() throws if called.
            $model = new class extends MemberModel {
                public function __construct() {}
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for userId <= 0');
                }
            };

            $this->assertFalse($model->hasAdditionalRecord(0));
        }

        public function testHasAdditionalRecordReturnsFalseWhenUserIdIsNegative(): void
        {
            $model = new class extends MemberModel {
                public function __construct() {}
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for userId <= 0');
                }
            };

            $this->assertFalse($model->hasAdditionalRecord(-7));
        }

        public function testHasAdditionalRecordReturnsTrueWhenDbFindsRow(): void
        {
            [$model] = $this->makeModel(loadResult: '1');

            $this->assertTrue($model->hasAdditionalRecord(5));
        }

        public function testHasAdditionalRecordReturnsFalseWhenDbReturnsNull(): void
        {
            [$model] = $this->makeModel(loadResult: null);

            $this->assertFalse($model->hasAdditionalRecord(5));
        }

        // -------------------------------------------------------------------
        // ensureAdditionalRecord() — guard clauses
        // -------------------------------------------------------------------

        public function testEnsureAdditionalRecordReturnsFalseWhenUserIdIsZero(): void
        {
            $model = new class extends MemberModel {
                public function __construct() {}
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for userId <= 0');
                }
            };

            $this->assertFalse($model->ensureAdditionalRecord(0));
        }

        public function testEnsureAdditionalRecordReturnsFalseWhenUserIdIsNegative(): void
        {
            $model = new class extends MemberModel {
                public function __construct() {}
                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for userId <= 0');
                }
            };

            $this->assertFalse($model->ensureAdditionalRecord(-3));
        }

        public function testEnsureAdditionalRecordReturnsTrueWhenRecordAlreadyExists(): void
        {
            // Override hasAdditionalRecord() to report the row already exists.
            // ensureAdditionalRecord() must return true immediately without
            // touching DB or Factory::getUser().
            $model = new class extends MemberModel {
                public function __construct() {}

                public function hasAdditionalRecord(int $userId): bool
                {
                    return true;
                }

                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called when record already exists');
                }
            };

            $this->assertTrue($model->ensureAdditionalRecord(42));
        }

        // -------------------------------------------------------------------
        // saveToTable() — INSERT path
        // -------------------------------------------------------------------

        public function testSaveToTableInsertUsesEmptyStringForMissingOptionalFields(): void
        {
            /** @var TestableSiteMemberModel $model */
            /** @var MemberQueryBuilder $qb */
            [$model, $qb] = $this->makeModel();

            $model->saveToTable(42, ['firstname' => 'Ada'], insert: true);

            $this->assertStringContainsString("'42'", $qb->capturedValues, 'id must appear in values');
            $this->assertStringContainsString("'Ada'", $qb->capturedValues, 'firstname must appear in values');
            $this->assertStringContainsString("''", $qb->capturedValues, 'missing fields must default to empty string');
        }

        public function testSaveToTableInsertWithAllFieldsPopulated(): void
        {
            [$model, $qb] = $this->makeModel();

            $data = [
                'firstname' => 'Jan',
                'street'    => 'Kerkstraat',
                'number'    => '12',
                'bus'       => 'A',
                'postcode'  => '9000',
                'city'      => 'Gent',
                'phone'     => '0475000000',
            ];

            $model->saveToTable(7, $data, insert: true);

            $this->assertStringContainsString("'7'", $qb->capturedValues);
            $this->assertStringContainsString("'Jan'", $qb->capturedValues);
            $this->assertStringContainsString("'Kerkstraat'", $qb->capturedValues);
            $this->assertStringContainsString("'12'", $qb->capturedValues);
            $this->assertStringContainsString("'9000'", $qb->capturedValues);
            $this->assertStringContainsString("'Gent'", $qb->capturedValues);
        }

        public function testSaveToTableInsertWithNoOptionalFieldsYieldsOnlyEmptyStrings(): void
        {
            [$model, $qb] = $this->makeModel();

            $model->saveToTable(1, [], insert: true);

            $this->assertSame(
                "'1','','','','','','',''",
                $qb->capturedValues,
                'Every missing optional field must default to an empty quoted string'
            );
        }

        // -------------------------------------------------------------------
        // saveToTable() — UPDATE path
        // -------------------------------------------------------------------

        public function testSaveToTableUpdateProducesSetFieldsForAllColumns(): void
        {
            [$model, $qb] = $this->makeModel();

            $data = [
                'firstname' => 'Luc',
                'street'    => 'Dorpstraat',
                'number'    => '5',
                'bus'       => '',
                'postcode'  => '2000',
                'city'      => 'Antwerpen',
                'phone'     => '03456789',
            ];

            $model->saveToTable(3, $data, insert: false);

            $this->assertCount(7, $qb->capturedSetFields);
        }

        public function testSaveToTableUpdateUsesEmptyStringForMissingOptionalFields(): void
        {
            [$model, $qb] = $this->makeModel();

            $model->saveToTable(3, ['firstname' => 'Mia'], insert: false);

            $this->assertCount(7, $qb->capturedSetFields);

            $setString = implode(', ', $qb->capturedSetFields);
            $this->assertStringContainsString("'Mia'", $setString, 'firstname must appear in SET');
            $this->assertStringContainsString("''", $setString, 'missing fields must default to empty string in SET');
        }

        public function testSaveToTableUpdateExcludesIdFromSetClause(): void
        {
            [$model, $qb] = $this->makeModel();

            $model->saveToTable(10, ['firstname' => 'Test'], insert: false);

            $setString = implode(', ', $qb->capturedSetFields);

            $this->assertStringNotContainsString('`id`', $setString);
        }

        // -------------------------------------------------------------------
        // saveToTable() — routing: insert vs. update flag
        // -------------------------------------------------------------------

        public function testSaveToTableDefaultsToUpdateMode(): void
        {
            [$model, $qb] = $this->makeModel();

            $model->saveToTable(5, ['firstname' => 'Default']);

            $this->assertNotEmpty($qb->capturedSetFields, 'Default mode must produce SET fields (update)');
            $this->assertSame('', $qb->capturedValues, 'Default mode must not call values() (update path)');
        }

        public function testSaveToTableInsertModePopulatesValuesNotSetFields(): void
        {
            [$model, $qb] = $this->makeModel();

            $model->saveToTable(5, ['firstname' => 'Insert'], insert: true);

            $this->assertNotEmpty($qb->capturedValues, 'Insert mode must call values()');
            $this->assertEmpty($qb->capturedSetFields, 'Insert mode must not call set() (update fields)');
        }

        // -------------------------------------------------------------------
        // Legacy value-extraction test (mirrors the logic inline)
        // -------------------------------------------------------------------

        public function testAdditionalMemberValuesUseDefaultsForMissingKeys(): void
        {
            $data = [
                'firstname' => 'Ada',
            ];

            $values = $this->buildAdditionalMemberValues(42, $data);

            $this->assertSame(
                [42, 'Ada', '', '', '', '', '', ''],
                $values
            );
        }

        /**
         * Mirror the value extraction used by MemberModel::saveToTable().
         *
         * @param   int    $id    Member id.
         * @param   array  $data  Form payload.
         *
         * @return  array
         */
        private function buildAdditionalMemberValues(int $id, array $data): array
        {
            return [
                $id,
                $data['firstname'] ?? '',
                $data['street'] ?? '',
                $data['number'] ?? '',
                $data['bus'] ?? '',
                $data['postcode'] ?? '',
                $data['city'] ?? '',
                $data['phone'] ?? '',
            ];
        }
    }
}
