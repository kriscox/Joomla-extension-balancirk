<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use PHPUnit\Framework\TestCase;
use CoCoCo\Component\Balancirk\Site\Model\MemberModel;

// AdminModel stub — may already be aliased by a previously loaded test file.
if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

// ---------------------------------------------------------------------------
// DB stubs for saveToTable() tests
//
// The recording query builder captures what the model passes to values() and
// set() so assertions can verify that missing optional fields default to ''.
// ---------------------------------------------------------------------------

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
}

/**
 * DB stub that wraps a shared MemberQueryBuilder so the test can inspect the
 * query the model would have sent.
 */
final class MemberFakeDatabase
{
    public function __construct(private readonly MemberQueryBuilder $qb) {}

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
 * Unit tests for site MemberModel::saveToTable().
 *
 * The site model's saveToTable() uses null-coalescing defaults ($data[key] ?? '')
 * for every optional column.  This prevents a PHP warning / incomplete INSERT
 * when optional registration fields are absent from the submitted form data —
 * a real-world bug that occurred when the API or a minimal registration form
 * omitted fields like street, bus, or postcode.
 *
 * Tests also verify the insert vs. update routing (the $insert flag).
 *
 * @since  1.3.8
 */
class MemberModelTest extends TestCase
{
    private function makeModel(): array
    {
        $qb = new MemberQueryBuilder();
        $db = new MemberFakeDatabase($qb);
        $model = new TestableSiteMemberModel($db);

        return [$model, $qb, $db];
    }

    // -----------------------------------------------------------------------
    // Insert path
    // -----------------------------------------------------------------------

    public function testSaveToTableInsertUsesEmptyStringForMissingOptionalFields(): void
    {
        /** @var TestableSiteMemberModel $model */
        /** @var MemberQueryBuilder $qb */
        [$model, $qb] = $this->makeModel();

        // Only firstname is supplied; all other fields are absent.
        $model->saveToTable(42, ['firstname' => 'Ada'], insert: true);

        // The values string must contain the id and firstname, with '' for the rest.
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

        // All seven optional columns (firstname…phone) default to ''
        $this->assertSame(
            "'1','','','','','','',''",
            $qb->capturedValues,
            'Every missing optional field must default to an empty quoted string'
        );
    }

    // -----------------------------------------------------------------------
    // Update path
    // -----------------------------------------------------------------------

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

        // There must be 7 set fields (id is excluded from SET).
        $this->assertCount(7, $qb->capturedSetFields);
    }

    public function testSaveToTableUpdateUsesEmptyStringForMissingOptionalFields(): void
    {
        [$model, $qb] = $this->makeModel();

        // Only firstname is supplied.
        $model->saveToTable(3, ['firstname' => 'Mia'], insert: false);

        $this->assertCount(7, $qb->capturedSetFields);

        // All 7 SET fragments must be present; missing fields default to ''
        $setString = implode(', ', $qb->capturedSetFields);
        $this->assertStringContainsString("'Mia'", $setString, 'firstname must appear in SET');
        $this->assertStringContainsString("''", $setString, 'missing fields must default to empty string in SET');
    }

    public function testSaveToTableUpdateExcludesIdFromSetClause(): void
    {
        [$model, $qb] = $this->makeModel();

        $model->saveToTable(10, ['firstname' => 'Test'], insert: false);

        $setString = implode(', ', $qb->capturedSetFields);

        // The id column must NOT appear in the SET clause (it goes in WHERE).
        $this->assertStringNotContainsString('`id`', $setString);
    }

    // -----------------------------------------------------------------------
    // Routing — insert vs. update flag
    // -----------------------------------------------------------------------

    public function testSaveToTableDefaultsToUpdateMode(): void
    {
        [$model, $qb] = $this->makeModel();

        // Call without the $insert flag → should behave as update.
        $model->saveToTable(5, ['firstname' => 'Default']);

        // In update mode, capturedSetFields is populated; capturedValues stays empty.
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
}
