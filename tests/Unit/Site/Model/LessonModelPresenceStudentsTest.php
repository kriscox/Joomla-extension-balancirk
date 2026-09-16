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
 * Tests for LessonModel student queries used by attendance.
 *
 * Waiting-list students must appear on the presence form and in the
 * attendance overview, while capacity counting (getStudents) stays
 * enrolled-only.
 *
 * @since  1.3.22
 */
class LessonModelPresenceStudentsTest extends TestCase
{
    public function testGetPresenceStudentsReturnsLoadObjectListResult(): void
    {
        $expected = [
            (object) ['id' => 1, 'name' => 'Smith', 'firstname' => 'Alice', 'on_waiting_list' => 0],
            (object) ['id' => 2, 'name' => 'Jones', 'firstname' => 'Bob', 'on_waiting_list' => 1],
        ];

        [$db] = $this->makeFakeDb($expected);
        $model = $this->makeModelWithDb($db);

        $this->assertSame($expected, $model->getPresenceStudents(5));
    }

    public function testGetPresenceStudentsUsesExplicitLessonId(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getPresenceStudents(42);

        $this->assertStringContainsString('42', $qb->capturedWhere);
    }

    public function testGetPresenceStudentsFallsBackToState(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDbAndState($db, 17);

        $model->getPresenceStudents();

        $this->assertStringContainsString('17', $qb->capturedWhere);
    }

    public function testGetPresenceStudentsDoesNotFilterBySubscriptionStatus(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getPresenceStudents(1);

        $allJoinConditions = implode(' ', $qb->joinArgs);
        $this->assertStringContainsString('s.student = a.id', $allJoinConditions);
        $this->assertStringNotContainsString(
            's.subscribed = 0',
            $allJoinConditions,
            'Presence list must include waiting-list students'
        );
        $this->assertStringNotContainsString(
            's.subscribed = 1',
            $allJoinConditions,
            'Presence list must include enrolled students'
        );
    }

    public function testGetPresenceStudentsJoinsPresencesForLastAttendance(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getPresenceStudents(1);

        $allJoinConditions = implode(' ', $qb->joinArgs);
        $this->assertStringContainsString('#__balancirk_presences', $allJoinConditions);
        $this->assertStringContainsString('p.student = a.id', $allJoinConditions);
    }

    public function testGetPresenceStudentsOrdersEnrolledStudentsFirst(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getPresenceStudents(1);

        $this->assertSame(['s.subscribed', 'a.name', 'a.firstname'], $qb->capturedOrder);
    }

    public function testGetStudentsStillFiltersEnrolledOnly(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getStudents(1);

        $allJoinConditions = implode(' ', $qb->joinArgs);
        $this->assertStringContainsString('s.subscribed = 0', $allJoinConditions);
        $this->assertStringNotContainsString('s.subscribed = 1', $allJoinConditions);
        $this->assertSame(['a.name', 'a.firstname'], $qb->capturedOrder);
    }

    public function testGetWaitingListStudentsJoinsPresencesForLastAttendance(): void
    {
        [$db, $qb] = $this->makeFakeDb([]);
        $model = $this->makeModelWithDb($db);

        $model->getWaitingListStudents(1);

        $allJoinConditions = implode(' ', $qb->joinArgs);
        $this->assertStringContainsString('s.subscribed = 1', $allJoinConditions);
        $this->assertStringContainsString('#__balancirk_presences', $allJoinConditions);
    }

    /**
     * @param   array  $loadResult  Value returned by loadObjectList().
     *
     * @return  array{0: object, 1: object}
     */
    private function makeFakeDb(array $loadResult): array
    {
        $qb = new class {
            public string $capturedWhere = '';
            public mixed $capturedOrder = null;

            /** @var string[] */
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
                $this->capturedOrder = $o;

                return $this;
            }

            public function group(mixed ...$g): static
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
