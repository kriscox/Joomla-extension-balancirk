<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs: Table and DatabaseDriver are not in vendor, so define them
// here before loading StudentsTable.
// Bracketed namespace syntax is required when mixing multiple namespace blocks.
// ---------------------------------------------------------------------------

namespace Joomla\CMS\Table {
    if (!class_exists('Joomla\\CMS\\Table\\Table')) {
        /**
         * Minimal stub for Joomla\CMS\Table\Table.
         * The constructor ignores the database argument so the type hint does
         * not force a real DatabaseDriver to be provided in tests.
         * store() is a stub that returns true without side effects.
         * AllowDynamicProperties lets StudentsTable set arbitrary columns as
         * object properties without PHP 8.3 dynamic-property deprecations.
         */
        #[\AllowDynamicProperties]
        class Table
        {
            public function __construct(string $table, string $key, mixed $db)
            {
            }

            public function store($updateNulls = false)
            {
                return true;
            }
        }
    }
}

namespace Joomla\Database {
    if (!class_exists('Joomla\\Database\\DatabaseDriver')) {
        /**
         * Minimal stub for Joomla\Database\DatabaseDriver.
         * StudentsTable::__construct() is type-hinted against this class, so
         * we stub it here to satisfy the declaration.
         */
        class DatabaseDriver
        {
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Table {
    use CoCoCo\Component\Balancirk\Administrator\Table\StudentsTable;
    use Joomla\Database\DatabaseDriver;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for StudentsTable::store() — boolean column normalisation.
     *
     * Both increased_benefit and allow_photo are boolean database columns.
     * HTML checkboxes omit their key from POST when unchecked, so a save via
     * AdminModel could leave these columns with NULL or ''.  store() guards
     * against this by coercing any absent/falsy value to 0 and any truthy
     * value to 1 before delegating to the parent Joomla Table.
     *
     * Regression risk: without this coercion a boolean column might receive a
     * NULL, which MySQL may store as 0 silently but which could confuse
     * accounting export comparisons and future query optimisations.
     *
     * @since  1.3.27
     */
    class StudentsTableStoreTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // increased_benefit normalisation
        // -----------------------------------------------------------------------

        /**
         * When increased_benefit is null store() must normalise it to 0.
         *
         * @return void
         */
        public function testStoreNormalisesNullIncreasedBenefitToZero(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = null;
            $table->allow_photo = 1;

            $table->store();

            $this->assertSame(0, $table->increased_benefit);
        }

        /**
         * When increased_benefit is an empty string store() must normalise it to 0.
         *
         * @return void
         */
        public function testStoreNormalisesEmptyStringIncreasedBenefitToZero(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = '';
            $table->allow_photo = 0;

            $table->store();

            $this->assertSame(0, $table->increased_benefit);
        }

        /**
         * When increased_benefit is 1 store() must keep it as 1.
         *
         * @return void
         */
        public function testStoreKeepsIntegerOneIncreasedBenefit(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = 1;
            $table->allow_photo = 0;

            $table->store();

            $this->assertSame(1, $table->increased_benefit);
        }

        /**
         * When increased_benefit is '1' store() must normalise it to integer 1.
         *
         * @return void
         */
        public function testStoreNormalisesStringOneIncreasedBenefitToOne(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = '1';
            $table->allow_photo = 0;

            $table->store();

            $this->assertSame(1, $table->increased_benefit);
        }

        // -----------------------------------------------------------------------
        // allow_photo normalisation (existing column, same guard)
        // -----------------------------------------------------------------------

        /**
         * When allow_photo is null store() must normalise it to 0.
         *
         * @return void
         */
        public function testStoreNormalisesNullAllowPhotoToZero(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = 0;
            $table->allow_photo = null;

            $table->store();

            $this->assertSame(0, $table->allow_photo);
        }

        /**
         * When allow_photo is 1 store() must keep it as 1.
         *
         * @return void
         */
        public function testStoreKeepsIntegerOneAllowPhoto(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = 0;
            $table->allow_photo = 1;

            $table->store();

            $this->assertSame(1, $table->allow_photo);
        }

        // -----------------------------------------------------------------------
        // Return value propagation
        // -----------------------------------------------------------------------

        /**
         * store() must return the boolean result of parent::store().
         * The stub always returns true so this exercises the propagation path.
         *
         * @return void
         */
        public function testStoreReturnsTrueFromParent(): void
        {
            $table = $this->makeTable();
            $table->increased_benefit = 1;
            $table->allow_photo = 1;

            $this->assertTrue($table->store());
        }

        // -----------------------------------------------------------------------
        // Helper
        // -----------------------------------------------------------------------

        /**
         * Build a StudentsTable backed by the DatabaseDriver stub.
         *
         * @return StudentsTable
         */
        private function makeTable(): StudentsTable
        {
            return new StudentsTable(new DatabaseDriver());
        }
    }
}
