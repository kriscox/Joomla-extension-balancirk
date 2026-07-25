<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Tests for LessonModel::getWaitingListStudents().
 *
 * Key business rules under test:
 *  - waiting-list JOIN uses s.subscribed = 1 (not 0, which is the active-subscription value)
 *  - explicit lessonid is used as-is; null falls back to getState('lesson.id')
 *  - the method returns exactly whatever loadObjectList() returns
 *
 * @since  1.3.20
 */
class LessonModelWaitingListTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Return-value passthrough
    // -------------------------------------------------------------------------

    /**
     * getWaitingListStudents() must return the raw result from loadObjectList().
     *
     * @return void
     */
    public function testReturnsLoadObjectListResult(): void
    {
        $expected = [
            (object)['id' => 1, 'name' => 'Smith', 'firstname' => 'Alice', 'birthdate' => '2010-03-15'],
            (object)['id' => 2, 'name' => 'Jones', 'firstname' => 'Bob',   'birthdate' => '2011-07-22'],
        ];

        [$db] = $this->makeFakeDb($expected);
        $model = $this->makeModelWithDb($db);

        $result = $model->getWaitingListStudents(5);

        $this->assertSame($expected, $result);
    }

    /**
     * getWaitingListStudents() must return an empty array when no students are waiting.
     *
     * @return void
     */
    public function testReturnsEmptyArrayWhenNoWaitingStudents(): void
    {
        [$db] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $result = $model->getWaitingListStudents(1);

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // Lesson-id routing
    // -------------------------------------------------------------------------

    /**
     * When an explicit lessonid is supplied, it must appear in the WHERE condition.
     *
     * @return void
     */
    public function testExplicitLessonIdAppearsInWhereClause(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getWaitingListStudents(42);

        $this->assertStringContainsString('42', $qb->capturedWhere);
    }

    /**
     * When lessonid is null, getState('lesson.id') must be used instead.
     *
     * @return void
     */
    public function testNullLessonIdFallsBackToGetState(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDbAndState($db, 99);

        $model->getWaitingListStudents(null);

        $this->assertStringContainsString('99', $qb->capturedWhere);
    }

    /**
     * Omitting the lessonid argument (default null) also falls back to getState().
     *
     * @return void
     */
    public function testOmittedLessonIdFallsBackToGetState(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDbAndState($db, 7);

        $model->getWaitingListStudents();

        $this->assertStringContainsString('7', $qb->capturedWhere);
    }

    // -------------------------------------------------------------------------
    // Critical JOIN filter: subscribed = 1 (waiting list), NOT subscribed = 0
    // -------------------------------------------------------------------------

    /**
     * The INNER JOIN must filter on s.subscribed = 1 to select waiting-list
     * students only.  The enrolled-student query (getStudents) uses subscribed = 0;
     * mixing these up would silently return the wrong student set.
     *
     * @return void
     */
    public function testJoinFiltersOnSubscribedEqualsOne(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getWaitingListStudents(1);

        $this->assertNotEmpty($qb->joinArgs, 'At least one JOIN must be registered');

        $allJoinConditions = implode(' ', $qb->joinArgs);
        $this->assertStringContainsString(
            's.subscribed = 1',
            $allJoinConditions,
            'Waiting-list JOIN must use subscribed = 1'
        );
    }

    /**
     * The JOIN must NOT use subscribed = 0, which would accidentally return
     * enrolled students instead of waiting-list students.
     *
     * @return void
     */
    public function testJoinDoesNotFilterOnSubscribedEqualsZero(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getWaitingListStudents(1);

        $allJoinConditions = implode(' ', $qb->joinArgs);
        $this->assertStringNotContainsString(
            's.subscribed = 0',
            $allJoinConditions,
            'Waiting-list JOIN must not use subscribed = 0'
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a fake query builder and database pair.
     *
     * The query builder captures the WHERE condition and all JOIN conditions so
     * that tests can assert on them without needing a real database.
     *
     * @param   array  $loadResult  Value returned by loadObjectList().
     *
     * @return  array{0: object, 1: object}
     */
    private function makeFakeDb(array $loadResult): array
    {
        $qb = new class {
            /** @var string Last WHERE condition passed to where(). */
            public string $capturedWhere = '';

            /** @var string[] All JOIN conditions passed to join(). */
            public array $joinArgs = [];

            public function select(mixed $x): static
            {
                return $this;
            }
            public function from(mixed $t): static
            {
                return $this;
            }
            public function join(mixed $type, mixed $condition): static
            {
                $this->joinArgs[] = (string) $condition;
                return $this;
            }
            public function where(mixed $cond): static
            {
                $this->capturedWhere = (string) $cond;
                return $this;
            }
            public function order(mixed $o): static
            {
                return $this;
            }
        };

        $db = new class ($qb, $loadResult) {
            public function __construct(
                private readonly object $qb,
                private readonly array $loadResult
            ) {
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

            public function setQuery(mixed $q): static
            {
                return $this;
            }

            public function loadObjectList(): array
            {
                return $this->loadResult;
            }
        };

        return [$db, $qb];
    }

    /**
     * Create a LessonModel subclass that uses the given fake database and
     * returns null from getState() (simulates no persisted lesson state).
     *
     * @param   object  $db  Fake database instance.
     *
     * @return  LessonModel
     */
    private function makeModelWithDb(object $db): LessonModel
    {
        return new class ($db) extends LessonModel {
            public function __construct(private readonly object $db)
            {
            }
            public function getDatabase(): object
            {
                return $this->db;
            }
            public function getState($property = null, $default = null): mixed
            {
                return null;
            }
        };
    }

    /**
     * Create a LessonModel subclass that uses the given fake database and
     * returns $stateValue from getState() (simulates a persisted lesson id).
     *
     * @param   object  $db          Fake database instance.
     * @param   mixed   $stateValue  Value returned by getState().
     *
     * @return  LessonModel
     */
    private function makeModelWithDbAndState(object $db, mixed $stateValue): LessonModel
    {
        return new class ($db, $stateValue) extends LessonModel {
            public function __construct(
                private readonly object $db,
                private readonly mixed $stateValue
            ) {
            }
            public function getDatabase(): object
            {
                return $this->db;
            }
            public function getState($property = null, $default = null): mixed
            {
                return $this->stateValue;
            }
        };
    }
}
