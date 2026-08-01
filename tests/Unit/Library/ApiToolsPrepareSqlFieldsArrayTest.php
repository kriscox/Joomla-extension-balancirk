<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Library
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Library;

use PHPUnit\Framework\TestCase;
use Joomlaology\Traits\ApiTools;

/**
 * Tests for ApiTools::prepareSqlFieldsArray().
 *
 * Covers both the object path (uses raw string interpolation for values) and
 * the array path (delegates value quoting to $db->quote()).
 * Both paths share the same quoteName() call for column names.
 *
 * Key business rules:
 *  - Object input: boolean → literal '0'/'1', other → raw string interpolation
 *  - Array input:  boolean → $db->quote(0|1), other → $db->quote($value)
 *  - Empty object or empty array → ['success' => false]
 *  - Non-empty input → ['success' => true, 'fields' => [...]]
 *
 * @since  1.3.21
 */
class ApiToolsPrepareSqlFieldsArrayTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Object input path
    // -------------------------------------------------------------------------

    /**
     * An object with a single string property must produce one field entry with
     * the column name quoted and the value embedded via string interpolation.
     *
     * @return void
     */
    public function testObjectInputWithStringValueReturnsSuccessAndField(): void
    {
        $subject = $this->makeSubject();
        $data = (object)['name' => 'Alice'];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['fields']);
        $this->assertStringContainsString('`name`', $result['fields'][0]);
        $this->assertStringContainsString("'Alice'", $result['fields'][0]);
    }

    /**
     * Multiple properties on an object must produce one field entry per property.
     *
     * @return void
     */
    public function testObjectInputWithMultiplePropertiesReturnsMultipleFields(): void
    {
        $subject = $this->makeSubject();
        $data = (object)['first' => 'Alice', 'last' => 'Smith'];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['fields']);
    }

    /**
     * A boolean true property on an object must be stored as the literal string '1',
     * NOT passed through $db->quote().
     *
     * @return void
     */
    public function testObjectInputWithBooleanTrueConvertsToOne(): void
    {
        $subject = $this->makeSubject();
        $data = (object)['active' => true];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString("= '1'", $result['fields'][0]);
    }

    /**
     * A boolean false property on an object must be stored as the literal string '0'.
     *
     * @return void
     */
    public function testObjectInputWithBooleanFalseConvertsToZero(): void
    {
        $subject = $this->makeSubject();
        $data = (object)['active' => false];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString("= '0'", $result['fields'][0]);
    }

    /**
     * An empty object (no properties) must return ['success' => false].
     *
     * @return void
     */
    public function testEmptyObjectReturnsFailure(): void
    {
        $subject = $this->makeSubject();

        $result = $subject->callPrepareSqlFieldsArray(new \stdClass());

        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // Array input path
    // -------------------------------------------------------------------------

    /**
     * An associative array with a string value must produce one field entry using
     * $db->quote() for the value (distinguishing array from object path).
     *
     * @return void
     */
    public function testArrayInputWithStringValueReturnsSuccessAndField(): void
    {
        $subject = $this->makeSubject();
        $data = ['city' => 'Brussels'];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['fields']);
        $this->assertStringContainsString('`city`', $result['fields'][0]);
        // The array path uses db->quote(), which our fake wraps in single quotes.
        $this->assertStringContainsString("'Brussels'", $result['fields'][0]);
    }

    /**
     * Multiple entries in an array must produce one field entry per entry.
     *
     * @return void
     */
    public function testArrayInputWithMultipleEntriesReturnsMultipleFields(): void
    {
        $subject = $this->makeSubject();
        $data = ['a' => 'x', 'b' => 'y', 'c' => 'z'];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['fields']);
    }

    /**
     * A boolean true entry in an array must be quoted as the integer 1.
     *
     * @return void
     */
    public function testArrayInputWithBooleanTrueQuotesOne(): void
    {
        $subject = $this->makeSubject();
        $data = ['enabled' => true];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        // The fake quote() wraps its argument in single quotes: '1'
        $this->assertStringContainsString("'1'", $result['fields'][0]);
    }

    /**
     * A boolean false entry in an array must be quoted as the integer 0.
     *
     * @return void
     */
    public function testArrayInputWithBooleanFalseQuotesZero(): void
    {
        $subject = $this->makeSubject();
        $data = ['enabled' => false];

        $result = $subject->callPrepareSqlFieldsArray($data);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString("'0'", $result['fields'][0]);
    }

    /**
     * An empty array must return ['success' => false].
     *
     * @return void
     */
    public function testEmptyArrayReturnsFailure(): void
    {
        $subject = $this->makeSubject();

        $result = $subject->callPrepareSqlFieldsArray([]);

        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a test subject that uses ApiTools with a fake database injected via getDbo().
     *
     * @return object
     */
    private function makeSubject(): object
    {
        $fakeDb = new class {
            public function quoteName(string $name): string
            {
                return "`$name`";
            }

            public function quote(mixed $value): string
            {
                return "'$value'";
            }
        };

        return new class ($fakeDb) {
            use ApiTools;

            public function __construct(private readonly object $fakeDb)
            {
            }

            /** Expose the protected method for testing. */
            public function callPrepareSqlFieldsArray(mixed $data): mixed
            {
                return $this->prepareSqlFieldsArray($data);
            }

            protected function getDbo(): object
            {
                return $this->fakeDb;
            }
        };
    }
}
