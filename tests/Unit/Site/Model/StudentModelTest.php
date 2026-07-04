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
use CoCoCo\Component\Balancirk\Site\Model\StudentModel;

// AdminModel has no Joomla CMS in test environment; alias stdClass so the model can extend something.
if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Minimal fluent query-builder stub used by the DB mock below.
 *
 * Every builder method returns $this so the chained calls in the model
 * do not produce "call to method on non-object" errors.
 */
final class FakeQueryBuilder
{
    public function select(mixed $x): static { return $this; }
    public function from(mixed $x): static { return $this; }
    public function where(mixed $x): static { return $this; }
}

/**
 * Minimal database stub returned by the testable StudentModel subclass.
 *
 * Configurable results allow tests to exercise both the "found" and
 * "not found" branches of isPrimairyParent() without a real database.
 *
 * @since 1.3.8
 */
final class FakeDatabase
{
    public function __construct(
        private readonly bool $executeResult,
        private readonly int  $numRows
    ) {}

    public function getQuery(bool $new = false): FakeQueryBuilder
    {
        return new FakeQueryBuilder();
    }

    public function quoteName(mixed $x): string
    {
        return is_array($x)
            ? implode(', ', array_map(fn ($v) => "`$v`", $x))
            : "`$x`";
    }

    public function quote(mixed $x): string
    {
        return "'$x'";
    }

    public function setQuery(object $query): static
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
}

/**
 * Testable subclass that replaces the Joomla database accessor with our stub.
 *
 * All other StudentModel behaviour is inherited as-is.
 *
 * @since 1.3.8
 */
final class TestableStudentModel extends StudentModel
{
    public function __construct(private readonly FakeDatabase $fakeDb) {}

    /** @inheritDoc */
    public function getDatabase(): FakeDatabase
    {
        return $this->fakeDb;
    }
}

/**
 * Unit tests for StudentModel::isPrimairyParent().
 *
 * isPrimairyParent() is the primary-parent security gate used by both
 * StudentModel::save() (added in 8e270fa, security fix) and indirectly
 * by SubscriptionModel::canDelete().  Incorrect results allow non-primary
 * parents to overwrite student data or unsubscribe students they do not own.
 *
 * @since  1.3.8
 */
class StudentModelTest extends TestCase
{
    private function makeModel(bool $executeResult, int $numRows): TestableStudentModel
    {
        return new TestableStudentModel(new FakeDatabase($executeResult, $numRows));
    }

    public function testIsPrimairyParentReturnsTrueWhenDbFindsOneRow(): void
    {
        $model = $this->makeModel(executeResult: true, numRows: 1);

        $this->assertTrue($model->isPrimairyParent(parent: 42, student: 10));
    }

    public function testIsPrimairyParentReturnsFalseWhenDbFindsZeroRows(): void
    {
        $model = $this->makeModel(executeResult: true, numRows: 0);

        $this->assertFalse($model->isPrimairyParent(parent: 42, student: 10));
    }

    public function testIsPrimairyParentReturnsFalseWhenDbExecuteFails(): void
    {
        $model = $this->makeModel(executeResult: false, numRows: 0);

        $this->assertFalse($model->isPrimairyParent(parent: 42, student: 10));
    }

    public function testIsPrimairyParentReturnsTrueWhenDbFindsMultipleRows(): void
    {
        // More than one row still means the relationship exists.
        $model = $this->makeModel(executeResult: true, numRows: 3);

        $this->assertTrue($model->isPrimairyParent(parent: 1, student: 99));
    }

    public function testIsPrimairyParentAcceptsNullArgumentsWithoutError(): void
    {
        // Calling with nulls should not crash; the query will match no rows.
        $model = $this->makeModel(executeResult: true, numRows: 0);

        $this->assertFalse($model->isPrimairyParent(null, null));
    }

    public function testIsPrimairyParentDifferentParentStudentCombinations(): void
    {
        // DB is configured to return 1 row regardless — correct row filtering
        // is the DB's responsibility; the model only evaluates the row count.
        $model = $this->makeModel(executeResult: true, numRows: 1);

        $this->assertTrue($model->isPrimairyParent(parent: 7, student: 3));
        $this->assertTrue($model->isPrimairyParent(parent: 7, student: 4));
    }
}
