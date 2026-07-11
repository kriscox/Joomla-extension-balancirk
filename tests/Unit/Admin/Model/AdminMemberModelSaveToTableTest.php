<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model;

use PHPUnit\Framework\TestCase;
use CoCoCo\Component\Balancirk\Administrator\Model\MemberModel as AdminMemberModel;

// AdminModel stub — may already be aliased by a previously loaded test file.
if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

// ---------------------------------------------------------------------------
// DB stubs for saveToTable() tests — admin model shares the same column list
// and null-coalescing defaulting logic as the site model.
// ---------------------------------------------------------------------------

/** Records the values string passed to ->values() and fields passed to ->set(). */
final class AdminMemberQueryBuilder
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
}

/**
 * DB stub wrapping a shared AdminMemberQueryBuilder.
 */
final class AdminMemberFakeDatabase
{
    public function __construct(private readonly AdminMemberQueryBuilder $qb) {}

    public function getQuery(bool $new = false): AdminMemberQueryBuilder
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
}

/** Testable subclass that injects our fake DB into the admin MemberModel. */
final class TestableAdminMemberModel extends AdminMemberModel
{
    public function __construct(private readonly AdminMemberFakeDatabase $fakeDb) {}

    public function getDatabase(): AdminMemberFakeDatabase
    {
        return $this->fakeDb;
    }
}

/**
 * Unit tests for admin MemberModel::saveToTable().
 *
 * The admin model's saveToTable() uses the same null-coalescing defaults as
 * the site model:  every optional column falls back to '' when the key is
 * absent from $data.  This ensures the admin "edit member" screen and the
 * registration API don't produce incomplete INSERT/UPDATE statements when
 * fields like bus, postcode, or phone are not submitted.
 *
 * @since  1.3.17
 */
class AdminMemberModelSaveToTableTest extends TestCase
{
    private function makeModel(): array
    {
        $qb = new AdminMemberQueryBuilder();
        $db = new AdminMemberFakeDatabase($qb);
        $model = new TestableAdminMemberModel($db);

        return [$model, $qb];
    }

    // -----------------------------------------------------------------------
    // INSERT path
    // -----------------------------------------------------------------------

    public function testAdminSaveToTableInsertUsesEmptyStringForMissingOptionalFields(): void
    {
        [$model, $qb] = $this->makeModel();

        // Only firstname is supplied; all other fields are absent.
        $model->saveToTable(10, ['firstname' => 'Sara'], insert: true);

        $this->assertStringContainsString("'10'", $qb->capturedValues, 'id must appear in values');
        $this->assertStringContainsString("'Sara'", $qb->capturedValues, 'firstname must appear in values');
        $this->assertStringContainsString("''", $qb->capturedValues, 'missing fields must default to empty string');
    }

    public function testAdminSaveToTableInsertWithAllFieldsPopulated(): void
    {
        [$model, $qb] = $this->makeModel();

        $data = [
            'firstname' => 'Tom',
            'street'    => 'Marktplein',
            'number'    => '3',
            'bus'       => 'B',
            'postcode'  => '1000',
            'city'      => 'Brussel',
            'phone'     => '02 500 00 00',
        ];

        $model->saveToTable(15, $data, insert: true);

        $this->assertStringContainsString("'15'", $qb->capturedValues);
        $this->assertStringContainsString("'Tom'", $qb->capturedValues);
        $this->assertStringContainsString("'Marktplein'", $qb->capturedValues);
        $this->assertStringContainsString("'1000'", $qb->capturedValues);
        $this->assertStringContainsString("'Brussel'", $qb->capturedValues);
    }

    public function testAdminSaveToTableInsertWithNoOptionalFieldsYieldsOnlyEmptyStrings(): void
    {
        [$model, $qb] = $this->makeModel();

        $model->saveToTable(2, [], insert: true);

        // All 7 optional columns default to ''
        $this->assertSame(
            "'2','','','','','','',''",
            $qb->capturedValues,
            'Every missing optional field must default to an empty quoted string'
        );
    }

    // -----------------------------------------------------------------------
    // UPDATE path
    // -----------------------------------------------------------------------

    public function testAdminSaveToTableUpdateProducesSevenSetFields(): void
    {
        [$model, $qb] = $this->makeModel();

        $data = [
            'firstname' => 'Els',
            'street'    => 'Stationslaan',
            'number'    => '22',
            'bus'       => '',
            'postcode'  => '3000',
            'city'      => 'Leuven',
            'phone'     => '016 00 00 00',
        ];

        $model->saveToTable(8, $data, insert: false);

        // id is excluded from the SET clause; all 7 other columns appear.
        $this->assertCount(7, $qb->capturedSetFields);
    }

    public function testAdminSaveToTableUpdateUsesEmptyStringForMissingOptionalFields(): void
    {
        [$model, $qb] = $this->makeModel();

        // Only firstname is supplied.
        $model->saveToTable(8, ['firstname' => 'Pia'], insert: false);

        $this->assertCount(7, $qb->capturedSetFields);

        $setString = implode(', ', $qb->capturedSetFields);
        $this->assertStringContainsString("'Pia'", $setString, 'firstname must appear in SET');
        $this->assertStringContainsString("''", $setString, 'missing fields must default to empty string in SET');
    }

    public function testAdminSaveToTableUpdateExcludesIdFromSetClause(): void
    {
        [$model, $qb] = $this->makeModel();

        $model->saveToTable(20, ['firstname' => 'Nick'], insert: false);

        $setString = implode(', ', $qb->capturedSetFields);

        $this->assertStringNotContainsString('`id`', $setString);
    }

    // -----------------------------------------------------------------------
    // Routing: insert vs update flag
    // -----------------------------------------------------------------------

    public function testAdminSaveToTableDefaultsToUpdateMode(): void
    {
        [$model, $qb] = $this->makeModel();

        $model->saveToTable(5, ['firstname' => 'Default']);

        $this->assertNotEmpty($qb->capturedSetFields, 'Default mode must produce SET fields (update)');
        $this->assertSame('', $qb->capturedValues, 'Default mode must not call values() (update path)');
    }

    public function testAdminSaveToTableInsertModePopulatesValuesNotSetFields(): void
    {
        [$model, $qb] = $this->makeModel();

        $model->saveToTable(5, ['firstname' => 'New'], insert: true);

        $this->assertNotEmpty($qb->capturedValues, 'Insert mode must call values()');
        $this->assertEmpty($qb->capturedSetFields, 'Insert mode must not call set() (update fields)');
    }

    // -----------------------------------------------------------------------
    // save() dispatch logic
    // -----------------------------------------------------------------------

    public function testAdminSaveDispatchesToRegisterWhenIdIsZero(): void
    {
        $registerCalled = false;
        $editCalled = false;

        $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
            public function __construct(
                private bool &$registerCalled,
                private bool &$editCalled
            ) {}

            public function register($data)
            {
                $this->registerCalled = true;
                return true;
            }

            public function edit($data)
            {
                $this->editCalled = true;
                return true;
            }
        };

        $model->save(['id' => 0, 'firstname' => 'Test']);

        $this->assertTrue($registerCalled, 'save() with id=0 must call register()');
        $this->assertFalse($editCalled, 'save() with id=0 must not call edit()');
    }

    public function testAdminSaveDispatchesToEditWhenIdIsPositive(): void
    {
        $registerCalled = false;
        $editCalled = false;

        $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
            public function __construct(
                private bool &$registerCalled,
                private bool &$editCalled
            ) {}

            public function register($data)
            {
                $this->registerCalled = true;
                return true;
            }

            public function edit($data)
            {
                $this->editCalled = true;
                return true;
            }
        };

        $model->save(['id' => 5, 'firstname' => 'Existing']);

        $this->assertFalse($registerCalled, 'save() with id>0 must not call register()');
        $this->assertTrue($editCalled, 'save() with id>0 must call edit()');
    }

    public function testAdminSaveForwardsIdToEditData(): void
    {
        $capturedData = null;

        $model = new class ($capturedData) extends AdminMemberModel {
            public function __construct(private mixed &$capturedData) {}

            public function register($data) { return true; }

            public function edit($data)
            {
                $this->capturedData = $data;
                return true;
            }
        };

        $model->save(['id' => 42, 'firstname' => 'Check']);

        $this->assertSame(42, $capturedData['id'], 'save() must forward the resolved id in the data array');
    }
}
