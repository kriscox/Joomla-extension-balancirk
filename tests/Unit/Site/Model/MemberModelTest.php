<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use CoCoCo\Component\Balancirk\Site\Model\MemberModel;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Tests for member additional-field handling and saveToTable() SQL dispatch.
 *
 * @since  1.3.17
 */
class MemberModelTest extends TestCase
{
    /**
     * Member additional columns should tolerate missing optional keys.
     *
     * @return void
     */
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

    // -------------------------------------------------------------------------
    // saveToTable() — INSERT branch
    // -------------------------------------------------------------------------

    /**
     * saveToTable() with $insert=true must build an INSERT statement.
     *
     * @return void
     */
    public function testSaveToTableInsertBuildsInsertQuery(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(42, ['firstname' => 'Ada', 'city' => 'Gent'], true);

        $this->assertTrue($qb->wasInsertCalled, 'INSERT must be used when $insert=true');
        $this->assertFalse($qb->wasUpdateCalled, 'UPDATE must not be used when $insert=true');
    }

    /**
     * saveToTable() INSERT must include the provided values.
     *
     * @return void
     */
    public function testSaveToTableInsertCapturesSuppliedValues(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(7, ['firstname' => 'Lena', 'city' => 'Brussel'], true);

        $this->assertStringContainsString("'Lena'", $qb->capturedValues);
        $this->assertStringContainsString("'Brussel'", $qb->capturedValues);
    }

    /**
     * saveToTable() INSERT must substitute empty string for missing optional keys.
     *
     * @return void
     */
    public function testSaveToTableInsertDefaultsMissingKeysToEmptyString(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(99, ['firstname' => 'Bob'], true);

        // street, number, bus, postcode, city, phone all missing → ''
        $this->assertStringContainsString("''", $qb->capturedValues);
    }

    // -------------------------------------------------------------------------
    // saveToTable() — UPDATE branch
    // -------------------------------------------------------------------------

    /**
     * saveToTable() with $insert=false must build an UPDATE statement.
     *
     * @return void
     */
    public function testSaveToTableUpdateBuildsUpdateQuery(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(3, ['firstname' => 'Tom'], false);

        $this->assertTrue($qb->wasUpdateCalled, 'UPDATE must be used when $insert=false');
        $this->assertFalse($qb->wasInsertCalled, 'INSERT must not be used when $insert=false');
    }

    /**
     * saveToTable() UPDATE must produce exactly seven SET fields (all non-id columns).
     *
     * @return void
     */
    public function testSaveToTableUpdateProducesSevenSetFields(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(3, ['firstname' => 'Tom', 'city' => 'Antwerp'], false);

        $this->assertCount(7, $qb->capturedSetFields, 'Exactly 7 non-id columns must be SET');
    }

    /**
     * saveToTable() UPDATE must include supplied field values in SET clauses.
     *
     * @return void
     */
    public function testSaveToTableUpdateCapturesSuppliedValues(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(3, ['firstname' => 'Tom', 'city' => 'Antwerp'], false);

        $allFields = implode(' ', $qb->capturedSetFields);
        $this->assertStringContainsString("'Tom'", $allFields);
        $this->assertStringContainsString("'Antwerp'", $allFields);
    }

    /**
     * saveToTable() UPDATE must substitute empty string for missing optional keys.
     *
     * @return void
     */
    public function testSaveToTableUpdateDefaultsMissingKeysToEmptyString(): void
    {
        [$db, $qb] = $this->makeFakeDb();

        $model = $this->makeModelWithDb($db);
        $model->saveToTable(3, ['firstname' => 'Tom'], false);

        $allFields = implode(' ', $qb->capturedSetFields);
        $this->assertStringContainsString("''", $allFields);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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

    /**
     * Create a fake query builder and database pair.
     *
     * @return array{0: object, 1: object}
     */
    private function makeFakeDb(): array
    {
        $qb = new class {
            public bool $wasInsertCalled = false;
            public bool $wasUpdateCalled = false;
            public string $capturedValues = '';
            public array $capturedSetFields = [];

            public function insert(mixed $table): static
            {
                $this->wasInsertCalled = true;
                return $this;
            }
            public function update(mixed $table): static
            {
                $this->wasUpdateCalled = true;
                return $this;
            }
            public function columns(mixed $cols): static
            {
                return $this;
            }
            public function values(string $v): static
            {
                $this->capturedValues = $v;
                return $this;
            }
            public function set(array $fields): static
            {
                $this->capturedSetFields = $fields;
                return $this;
            }
            public function where(mixed $cond): static
            {
                return $this;
            }
            public function select(mixed $x): static
            {
                return $this;
            }
            public function from(mixed $t): static
            {
                return $this;
            }
            public function join(mixed ...$args): static
            {
                return $this;
            }
            public function order(mixed $o): static
            {
                return $this;
            }
            public function bind(mixed $col, mixed $val, mixed $type = null): static
            {
                return $this;
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
            public function quoteName(mixed $n, mixed $a = null): mixed
            {
                if (is_array($n)) {
                    return array_map(static fn($x) => "`$x`", $n);
                }
                return "`$n`";
            }
            public function quote(mixed $v): string
            {
                return "'$v'";
            }
            public function setQuery(mixed $q): static
            {
                return $this;
            }
            public function execute(): void
            {
            }
            public function loadResult(): mixed
            {
                return null;
            }
            public function loadObjectList(): array
            {
                return [];
            }
        };

        return [$db, $qb];
    }

    /**
     * Create a MemberModel stub that returns the given fake database.
     *
     * @param   object  $db  Fake database instance.
     *
     * @return  MemberModel
     */
    private function makeModelWithDb(object $db): MemberModel
    {
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
