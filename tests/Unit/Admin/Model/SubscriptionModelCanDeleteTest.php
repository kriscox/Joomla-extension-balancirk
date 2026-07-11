<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla\CMS stubs — declared once per process; guarded by class_exists so
// they do not interfere with other test files that may run in the same process.
// ---------------------------------------------------------------------------
namespace Joomla\CMS {
    if (!class_exists('Joomla\\CMS\\Factory')) {
        /**
         * Minimal Factory stub used only by the canDelete path under test.
         * Tests inject a custom application via setApp() before each assertion.
         */
        class Factory
        {
            private static mixed $app = null;

            public static function setApp(mixed $app): void
            {
                self::$app = $app;
            }

            public static function getApplication(): mixed
            {
                return self::$app;
            }
        }
    }
}

namespace Joomla\CMS\MVC\Model {
    if (!class_exists('Joomla\\CMS\\MVC\\Model\\AdminModel')) {
        class AdminModel {}
    }
}

// ---------------------------------------------------------------------------
// Test namespace — helper stubs + TestCase
// ---------------------------------------------------------------------------
namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {

    use PHPUnit\Framework\TestCase;
    use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel;

    // Alias any remaining un-stubbed Joomla classes the model imports.
    if (!class_exists('Joomla\\CMS\\Object\\CMSObject')) {
        \class_alias(\stdClass::class, 'Joomla\\CMS\\Object\\CMSObject');
    }
    if (!class_exists('Joomla\\CMS\\Helper\\ContentHelper')) {
        \class_alias(\stdClass::class, 'Joomla\\CMS\\Helper\\ContentHelper');
    }
    if (!class_exists('Joomla\\CMS\\Component\\ComponentHelper')) {
        \class_alias(\stdClass::class, 'Joomla\\CMS\\Component\\ComponentHelper');
    }
    if (!class_exists('Joomla\\CMS\\Mail\\MailerFactoryInterface')) {
        \class_alias(\stdClass::class, 'Joomla\\CMS\\Mail\\MailerFactoryInterface');
    }

    // -----------------------------------------------------------------------
    // Minimal fluent query-builder stub
    // -----------------------------------------------------------------------

    /** Fluent query-builder stub that handles all chained builder calls. */
    final class SubQueryBuilder
    {
        public function select(mixed $x): static { return $this; }
        public function from(mixed $x): static { return $this; }
        public function where(mixed $x): static { return $this; }
        public function insert(mixed $x): static { return $this; }
        public function columns(mixed $x): static { return $this; }
        public function values(mixed $x): static { return $this; }
        public function update(mixed $x): static { return $this; }
        public function set(mixed $x): static { return $this; }
        public function delete(mixed $x): static { return $this; }
        public function order(mixed $x): static { return $this; }
        public function join(mixed ...$args): static { return $this; }
    }

    /**
     * Configurable DB stub.
     *
     * loadResult() returns $firstLoad on the first call (primary-parent query)
     * and $secondLoad on subsequent calls (presence-count query).  This lets
     * tests configure both DB calls independently.
     */
    final class SubFakeDatabase
    {
        private int $loadCallCount = 0;

        public function __construct(
            private readonly mixed $firstLoad,
            private readonly mixed $secondLoad = null
        ) {}

        public function getQuery(bool $new = false): SubQueryBuilder
        {
            return new SubQueryBuilder();
        }

        public function quoteName(mixed $x): string
        {
            return is_array($x)
                ? implode(', ', \array_map(fn ($v) => "`$v`", $x))
                : "`$x`";
        }

        public function quote(mixed $x): string { return "'$x'"; }

        public function setQuery(object $q): static { return $this; }

        public function loadResult(): mixed
        {
            return $this->loadCallCount++ === 0 ? $this->firstLoad : $this->secondLoad;
        }

        public function execute(): bool { return true; }
        public function getNumRows(): int { return 0; }
    }

    /**
     * Testable subclass that replaces DB access and exposes canDelete() publicly.
     */
    final class ExposedSubscriptionModel extends SubscriptionModel
    {
        public function __construct(private readonly SubFakeDatabase $fakeDb) {}

        public function getDatabase(): SubFakeDatabase
        {
            return $this->fakeDb;
        }

        /** Expose the protected canDelete() method for white-box testing. */
        public function publicCanDelete(object $record): bool
        {
            return $this->canDelete($record);
        }
    }

    // -----------------------------------------------------------------------
    // Helper: build a minimal app stub that returns a configurable identity
    // -----------------------------------------------------------------------
    function makeAppStub(bool $isAdmin): object
    {
        return new class ($isAdmin) {
            public function __construct(private readonly bool $admin) {}
            public function getIdentity(): object
            {
                return new class ($this->admin) {
                    public int $id = 99;
                    public function __construct(private readonly bool $admin) {}
                    public function authorise(string $action, string $scope): bool
                    {
                        return $this->admin;
                    }
                };
            }
        };
    }

    /**
     * Unit tests for SubscriptionModel::canDelete().
     *
     * The method was refactored in commit e2374be to use direct DB queries
     * instead of model instantiation (which silently returned false in the API
     * context when models could not be instantiated).
     *
     * Coverage:
     * - Guard clauses for malformed records (no Factory/DB needed).
     * - Admin users bypass the parent-check and are always allowed.
     * - Non-primary parents are always denied.
     * - Primary parents are denied when presence count > 2.
     * - Primary parents are permitted when presence count <= 2.
     * - Presence threshold boundary (exactly 2) is correctly evaluated.
     *
     * @since  1.3.8
     */
    class SubscriptionModelCanDeleteTest extends TestCase
    {
        // -------------------------------------------------------------------
        // Guard-clause tests — Factory::getApplication() is never reached.
        // -------------------------------------------------------------------

        public function testCanDeleteReturnsFalseWhenLessonIsZero(): void
        {
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(null));
            $record = (object) ['lesson' => 0, 'student' => 5];

            $this->assertFalse($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsFalseWhenStudentIsZero(): void
        {
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(null));
            $record = (object) ['lesson' => 3, 'student' => 0];

            $this->assertFalse($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsFalseWhenBothFieldsAreEmpty(): void
        {
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(null));
            $record = (object) ['lesson' => 0, 'student' => 0];

            $this->assertFalse($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsFalseWhenLessonPropertyIsMissing(): void
        {
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(null));
            $record = (object) ['student' => 5];

            $this->assertFalse($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsFalseWhenStudentPropertyIsMissing(): void
        {
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(null));
            $record = (object) ['lesson' => 3];

            $this->assertFalse($model->publicCanDelete($record));
        }

        // -------------------------------------------------------------------
        // Admin bypass — any admin privilege returns true immediately.
        // -------------------------------------------------------------------

        public function testCanDeleteReturnsTrueForUserWithAdminPrivileges(): void
        {
            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: true));

            $model = new ExposedSubscriptionModel(new SubFakeDatabase(null));
            $record = (object) ['lesson' => 3, 'student' => 5];

            $this->assertTrue($model->publicCanDelete($record));
        }

        // -------------------------------------------------------------------
        // Non-primary parent — DB returns no primary-parent row.
        // -------------------------------------------------------------------

        public function testCanDeleteReturnsFalseForNonPrimaryParent(): void
        {
            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));

            // firstLoad = null → isPrimary check finds no row
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(firstLoad: null));
            $record = (object) ['lesson' => 3, 'student' => 5];

            $this->assertFalse($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsFalseForNonPrimaryParentWhenLoadResultIsZero(): void
        {
            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));

            // firstLoad = 0 (falsy) → not a primary parent
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(firstLoad: 0));
            $record = (object) ['lesson' => 3, 'student' => 5];

            $this->assertFalse($model->publicCanDelete($record));
        }

        // -------------------------------------------------------------------
        // Primary parent with presence threshold
        // -------------------------------------------------------------------

        public function testCanDeleteReturnsFalseWhenPresenceCountExceedsThreshold(): void
        {
            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));

            // firstLoad = '1' (is primary), secondLoad = 3 (> 2 presences)
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(firstLoad: '1', secondLoad: 3));
            $record = (object) ['lesson' => 3, 'student' => 5];

            $this->assertFalse($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsTrueWhenPresenceCountIsExactlyAtThreshold(): void
        {
            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));

            // firstLoad = '1' (is primary), secondLoad = 2 (exactly at threshold)
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(firstLoad: '1', secondLoad: 2));
            $record = (object) ['lesson' => 3, 'student' => 5];

            $this->assertTrue($model->publicCanDelete($record));
        }

        public function testCanDeleteReturnsTrueWhenPresenceCountIsZero(): void
        {
            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));

            // firstLoad = '1' (is primary), secondLoad = 0 (no presences)
            $model = new ExposedSubscriptionModel(new SubFakeDatabase(firstLoad: '1', secondLoad: 0));
            $record = (object) ['lesson' => 3, 'student' => 5];

            $this->assertTrue($model->publicCanDelete($record));
        }

        public function testPresenceThresholdBoundaryIsExactlyTwo(): void
        {
            $record = (object) ['lesson' => 1, 'student' => 1];

            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));
            $modelAt2 = new ExposedSubscriptionModel(new SubFakeDatabase('1', 2));
            $this->assertTrue(
                $modelAt2->publicCanDelete($record),
                'Exactly 2 presences must be allowed'
            );

            \Joomla\CMS\Factory::setApp(makeAppStub(isAdmin: false));
            $modelAt3 = new ExposedSubscriptionModel(new SubFakeDatabase('1', 3));
            $this->assertFalse(
                $modelAt3->publicCanDelete($record),
                '3 presences must be denied'
            );
        }
    }
}
