<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use CoCoCo\Component\Balancirk\Site\Model\SubscriptionModel;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Tests for SubscriptionModel guard clauses and subscriptionExists().
 *
 * getLessonsForStudent() has a guard that returns [] for any non-positive
 * studentId without touching the MVC factory — important because calling
 * getMVCFactory() with an invalid id can trigger warnings or fatal errors
 * when the factory is unavailable (CLI, API, unit-test context).
 *
 * subscriptionExists() performs a single-table SELECT and returns a bool;
 * a bug here would allow duplicate subscriptions or silently block valid ones.
 *
 * @since  1.3.21
 */
class SubscriptionModelGuardsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getLessonsForStudent() — guard clause
    // -------------------------------------------------------------------------

    /**
     * studentId = 0 must return an empty array without calling getMVCFactory().
     *
     * @return void
     */
    public function testGetLessonsForStudentReturnsEmptyForZeroId(): void
    {
        $model = $this->makeModelThatMustNotCallFactory();

        $this->assertSame([], $model->getLessonsForStudent(0));
    }

    /**
     * A negative studentId must return an empty array without calling getMVCFactory().
     *
     * @return void
     */
    public function testGetLessonsForStudentReturnsEmptyForNegativeId(): void
    {
        $model = $this->makeModelThatMustNotCallFactory();

        $this->assertSame([], $model->getLessonsForStudent(-5));
    }

    // -------------------------------------------------------------------------
    // subscriptionExists() — DB-backed bool result
    // -------------------------------------------------------------------------

    /**
     * When the DB returns a truthy value, subscriptionExists() must return true.
     *
     * @return void
     */
    public function testSubscriptionExistsReturnsTrueWhenRowFound(): void
    {
        $model = $this->makeModelWithLoadResult(1);
        $method = $this->getSubscriptionExistsMethod();

        $this->assertTrue($method->invoke($model, 10, 20));
    }

    /**
     * When the DB returns null (no row), subscriptionExists() must return false.
     *
     * @return void
     */
    public function testSubscriptionExistsReturnsFalseWhenNoRow(): void
    {
        $model = $this->makeModelWithLoadResult(null);
        $method = $this->getSubscriptionExistsMethod();

        $this->assertFalse($method->invoke($model, 10, 20));
    }

    /**
     * When the DB returns '0' (falsy string), subscriptionExists() must return false.
     *
     * @return void
     */
    public function testSubscriptionExistsReturnsFalseForFalsyDbValue(): void
    {
        $model = $this->makeModelWithLoadResult('0');
        $method = $this->getSubscriptionExistsMethod();

        $this->assertFalse($method->invoke($model, 3, 7));
    }

    /**
     * The WHERE clause must include both the student id and the lesson id so
     * the query is scoped to the exact student+lesson pair.
     *
     * @return void
     */
    public function testSubscriptionExistsWhereClauseIncludesBothIds(): void
    {
        $capturedWhereClauses = [];

        $qb = new class ($capturedWhereClauses) {
            public function __construct(private array &$captured)
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
            public function where(mixed $cond): static
            {
                $this->captured[] = (string) $cond;
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
                return "`$n`";
            }
            public function setQuery(mixed $q): static
            {
                return $this;
            }
            public function loadResult(): mixed
            {
                return null;
            }
        };

        $model = new class ($db) extends SubscriptionModel {
            public function __construct(private readonly object $db)
            {
            }
            public function getDatabase(): object
            {
                return $this->db;
            }
        };

        $method = $this->getSubscriptionExistsMethod();
        $method->invoke($model, 11, 22);

        $combined = implode(' ', $capturedWhereClauses);
        $this->assertStringContainsString('11', $combined, 'studentId must appear in WHERE');
        $this->assertStringContainsString('22', $combined, 'lessonId must appear in WHERE');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Reflect on the private subscriptionExists() method.
     *
     * @return ReflectionMethod
     */
    private function getSubscriptionExistsMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(SubscriptionModel::class, 'subscriptionExists');
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Create a SubscriptionModel subclass that throws if getMVCFactory() is called.
     *
     * @return SubscriptionModel
     */
    private function makeModelThatMustNotCallFactory(): SubscriptionModel
    {
        return new class extends SubscriptionModel {
            public function __construct()
            {
            }
            public function getMVCFactory(): never
            {
                throw new \LogicException('getMVCFactory() must not be called for non-positive studentId');
            }
        };
    }

    /**
     * Create a SubscriptionModel subclass whose fake DB returns a fixed loadResult() value.
     *
     * @param   mixed  $loadResultValue  Value returned by the fake DB.
     *
     * @return  SubscriptionModel
     */
    private function makeModelWithLoadResult(mixed $loadResultValue): SubscriptionModel
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
            public function where(mixed $c): static
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
                return "`$n`";
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

        return new class ($db) extends SubscriptionModel {
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
