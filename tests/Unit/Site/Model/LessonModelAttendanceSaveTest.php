<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use DateTime;
use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Tests for isAttendanceDateAllowed(), savePresence() and saveTeacher().
 *
 * savePresence() and saveTeacher() were void before 1.3.22 and now return bool.
 * The false paths — invalid lesson id, lesson not found, unparseable date, and
 * attendance not permitted by the lesson period — are new behavioural contracts
 * that must not regress silently.
 *
 * @since  1.3.22
 */
class LessonModelAttendanceSaveTest extends TestCase
{
    // -------------------------------------------------------------------------
    // isAttendanceDateAllowed() — guard clauses (no DB required)
    // -------------------------------------------------------------------------

    public function testIsAttendanceDateAllowedReturnsFalseForNullLesson(): void
    {
        $model = $this->makeResolvedPeriodModel(null);

        $this->assertFalse($model->isAttendanceDateAllowed(null, new DateTime('2026-09-07')));
    }

    public function testIsAttendanceDateAllowedReturnsFalseWhenLessonIdIsZero(): void
    {
        $model = $this->makeResolvedPeriodModel(null);
        $lesson = (object) ['id' => 0, 'start' => '2026-09-01', 'end' => '2026-09-30', 'lesdays' => 0];

        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-07')));
    }

    public function testIsAttendanceDateAllowedReturnsFalseWhenPeriodCannotBeResolved(): void
    {
        $model = $this->makeResolvedPeriodModel(null);
        $lesson = (object) ['id' => 5, 'lesdays' => 0];

        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-07')));
    }

    public function testIsAttendanceDateAllowedReturnsTrueForValidDateInsidePeriodWithNoLesdaysMask(): void
    {
        $period = [
            'start' => new DateTime('2026-09-01'),
            'end'   => new DateTime('2026-09-30'),
        ];
        $model = $this->makeResolvedPeriodModel($period);
        $lesson = (object) ['id' => 5, 'lesdays' => 0];

        $this->assertTrue($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-07')));
    }

    public function testIsAttendanceDateAllowedReturnsFalseForDateBeforePeriod(): void
    {
        $period = [
            'start' => new DateTime('2026-09-01'),
            'end'   => new DateTime('2026-09-30'),
        ];
        $model = $this->makeResolvedPeriodModel($period);
        $lesson = (object) ['id' => 5, 'lesdays' => 0];

        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-08-31')));
    }

    public function testIsAttendanceDateAllowedReturnsFalseForDateAfterPeriod(): void
    {
        $period = [
            'start' => new DateTime('2026-09-01'),
            'end'   => new DateTime('2026-09-30'),
        ];
        $model = $this->makeResolvedPeriodModel($period);
        $lesson = (object) ['id' => 5, 'lesdays' => 0];

        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-10-01')));
    }

    public function testIsAttendanceDateAllowedReturnsFalseForWrongWeekday(): void
    {
        // Mask 64 = Monday only.
        $period = [
            'start' => new DateTime('2026-09-01'),
            'end'   => new DateTime('2026-09-30'),
        ];
        $model = $this->makeResolvedPeriodModel($period);
        // 2026-09-08 is a Tuesday.
        $lesson = (object) ['id' => 5, 'lesdays' => 64];

        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-08')));
    }

    public function testIsAttendanceDateAllowedReturnsTrueForCorrectWeekday(): void
    {
        // Mask 64 = Monday only.
        $period = [
            'start' => new DateTime('2026-09-01'),
            'end'   => new DateTime('2026-09-30'),
        ];
        $model = $this->makeResolvedPeriodModel($period);
        // 2026-09-07 is a Monday.
        $lesson = (object) ['id' => 5, 'lesdays' => 64];

        $this->assertTrue($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-07')));
    }

    public function testIsAttendanceDateAllowedReturnsFalseForHolidayDate(): void
    {
        $period = [
            'start' => new DateTime('2026-09-01'),
            'end'   => new DateTime('2026-09-30'),
        ];
        // Holiday range covers 2026-09-07 (Monday).
        $holidays = [['start' => new DateTime('2026-09-07'), 'end' => new DateTime('2026-09-07')]];
        $model = $this->makeResolvedPeriodModelWithHolidays($period, $holidays);
        $lesson = (object) ['id' => 5, 'lesdays' => 64];

        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-07')));
    }

    // -------------------------------------------------------------------------
    // isAttendanceDateAllowed() — period resolved from item fields (no DB)
    // -------------------------------------------------------------------------

    public function testIsAttendanceDateAllowedResolvesFromItemWhenDbUnavailable(): void
    {
        // A model where getDatabase() throws so loadLessonDates() falls back to null
        // and resolveLessonPeriod() reads start/end directly from the lesson item.
        $model = $this->makeNoDatabaseModel();
        $lesson = (object) [
            'id' => 5,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
            'lesdays' => 0,
        ];

        $this->assertTrue($model->isAttendanceDateAllowed($lesson, new DateTime('2026-09-15')));
        $this->assertFalse($model->isAttendanceDateAllowed($lesson, new DateTime('2026-08-31')));
    }

    // -------------------------------------------------------------------------
    // savePresence() — guard clauses
    // -------------------------------------------------------------------------

    public function testSavePresenceReturnsFalseWhenLessonIdIsZero(): void
    {
        $model = $this->makeGuardModel(lessonItem: null, attendanceAllowed: false);

        $this->assertFalse($model->savePresence(0, '2026-09-07', []));
    }

    public function testSavePresenceReturnsFalseWhenLessonNotFound(): void
    {
        $model = $this->makeGuardModel(lessonItem: null, attendanceAllowed: false);

        $this->assertFalse($model->savePresence(5, '2026-09-07', []));
    }

    public function testSavePresenceReturnsFalseWhenDateIsInvalid(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $model = $this->makeGuardModel(lessonItem: $lesson, attendanceAllowed: true);

        $this->assertFalse($model->savePresence(5, 'not-a-date', []));
    }

    public function testSavePresenceReturnsFalseWhenAttendanceNotAllowed(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $model = $this->makeGuardModel(lessonItem: $lesson, attendanceAllowed: false);

        $this->assertFalse($model->savePresence(5, '2026-09-07', []));
    }

    public function testSavePresenceReturnsTrueAndDeletesThenInsertsForValidInput(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $ops = [];
        $model = $this->makeSuccessModel($lesson, $ops);

        $result = $model->savePresence(5, '2026-09-07', [10, 12]);

        $this->assertTrue($result);
        $this->assertContains('delete', $ops, 'Old presences must be deleted before inserting');
        $this->assertContains('insert', $ops, 'New presences must be inserted');
    }

    public function testSavePresenceInsertsOneRowPerStudent(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $ops = [];
        $model = $this->makeSuccessModel($lesson, $ops);

        $model->savePresence(5, '2026-09-07', [10, 12, 14]);

        $insertCount = count(array_filter($ops, static fn($op) => $op === 'insert'));
        $this->assertSame(3, $insertCount);
    }

    public function testSavePresenceDoesNotInsertWhenStudentListIsEmpty(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $ops = [];
        $model = $this->makeSuccessModel($lesson, $ops);

        $result = $model->savePresence(5, '2026-09-07', []);

        $this->assertTrue($result);
        $this->assertContains('delete', $ops);
        $this->assertNotContains('insert', $ops);
    }

    // -------------------------------------------------------------------------
    // saveTeacher() — guard clauses (same contract as savePresence)
    // -------------------------------------------------------------------------

    public function testSaveTeacherReturnsFalseWhenLessonIdIsZero(): void
    {
        $model = $this->makeGuardModel(lessonItem: null, attendanceAllowed: false);

        $this->assertFalse($model->saveTeacher(0, '2026-09-07', []));
    }

    public function testSaveTeacherReturnsFalseWhenLessonNotFound(): void
    {
        $model = $this->makeGuardModel(lessonItem: null, attendanceAllowed: false);

        $this->assertFalse($model->saveTeacher(5, '2026-09-07', []));
    }

    public function testSaveTeacherReturnsFalseWhenDateIsInvalid(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $model = $this->makeGuardModel(lessonItem: $lesson, attendanceAllowed: true);

        $this->assertFalse($model->saveTeacher(5, 'not-a-date', []));
    }

    public function testSaveTeacherReturnsFalseWhenAttendanceNotAllowed(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $model = $this->makeGuardModel(lessonItem: $lesson, attendanceAllowed: false);

        $this->assertFalse($model->saveTeacher(5, '2026-09-07', []));
    }

    public function testSaveTeacherReturnsTrueAndDeletesThenInsertsForValidInput(): void
    {
        $lesson = (object) ['id' => 5, 'lesdays' => 0];
        $ops = [];
        $model = $this->makeSuccessModel($lesson, $ops);

        $result = $model->saveTeacher(5, '2026-09-07', [3, 7]);

        $this->assertTrue($result);
        $this->assertContains('delete', $ops);
        $this->assertContains('insert', $ops);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Model that overrides resolveLessonPeriod() and getHolidayRanges().
     * Suitable for testing isAttendanceDateAllowed() in isolation.
     *
     * @param   array{start: DateTime, end: DateTime}|null  $period
     */
    private function makeResolvedPeriodModel(?array $period): LessonModel
    {
        return new class ($period) extends LessonModel {
            public function __construct(private readonly ?array $period)
            {
            }

            public function resolveLessonPeriod(?object $item): ?array
            {
                if ($item === null || (int) ($item->id ?? 0) <= 0) {
                    return null;
                }

                return $this->period;
            }

            public function getHolidayRanges(?DateTime $from = null, ?DateTime $to = null): array
            {
                return [];
            }
        };
    }

    /**
     * Model that overrides resolveLessonPeriod() and getHolidayRanges() with holidays.
     *
     * @param   array{start: DateTime, end: DateTime}       $period
     * @param   array<int, array{start: DateTime, end: DateTime}>  $holidays
     */
    private function makeResolvedPeriodModelWithHolidays(array $period, array $holidays): LessonModel
    {
        return new class ($period, $holidays) extends LessonModel {
            public function __construct(
                private readonly array $period,
                private readonly array $holidays
            ) {
            }

            public function resolveLessonPeriod(?object $item): ?array
            {
                if ($item === null || (int) ($item->id ?? 0) <= 0) {
                    return null;
                }

                return $this->period;
            }

            public function getHolidayRanges(?DateTime $from = null, ?DateTime $to = null): array
            {
                return $this->holidays;
            }
        };
    }

    /**
     * Model where getDatabase() throws, forcing resolveLessonPeriod() to
     * read start/end from the lesson item and getHolidayRanges() to return [].
     */
    private function makeNoDatabaseModel(): LessonModel
    {
        return new class extends LessonModel {
            public function getDatabase(): object
            {
                throw new \RuntimeException('No database in test');
            }

            public function getHolidayRanges(?DateTime $from = null, ?DateTime $to = null): array
            {
                return [];
            }
        };
    }

    /**
     * Model for guard-clause tests on savePresence/saveTeacher.
     * getItem() returns $lessonItem (possibly null).
     * isAttendanceDateAllowed() returns $attendanceAllowed.
     *
     * @param   object|null  $lessonItem
     * @param   bool         $attendanceAllowed
     */
    private function makeGuardModel(?object $lessonItem, bool $attendanceAllowed): LessonModel
    {
        return new class ($lessonItem, $attendanceAllowed) extends LessonModel {
            public function __construct(
                private readonly ?object $lessonItem,
                private readonly bool $attendanceAllowed
            ) {
            }

            public function getItem($pk = null): ?object
            {
                return $this->lessonItem;
            }

            public function isAttendanceDateAllowed(?object $lesson, mixed $date): bool
            {
                return $this->attendanceAllowed;
            }
        };
    }

    /**
     * Model for success-path tests on savePresence/saveTeacher.
     * Records 'delete' and 'insert' operations in $ops.
     *
     * @param   object   $lessonItem  Lesson returned by getItem().
     * @param   array    &$ops        Receives 'delete' and 'insert' strings.
     */
    private function makeSuccessModel(object $lessonItem, array &$ops): LessonModel
    {
        $qb = new class ($ops) {
            public function __construct(private array &$ops)
            {
            }

            public function delete(mixed $t): static
            {
                $this->ops[] = 'delete';

                return $this;
            }

            public function insert(mixed $t): static
            {
                $this->ops[] = 'insert';

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

            public function columns(mixed $c): static
            {
                return $this;
            }

            public function values(mixed $v): static
            {
                return $this;
            }

            public function where(mixed $c): static
            {
                return $this;
            }

            public function order(mixed $o): static
            {
                return $this;
            }

            public function clear(): static
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

            public function quoteName(mixed $n): string
            {
                if (is_array($n)) {
                    return implode(', ', array_map(static fn($x) => "`$x`", $n));
                }

                return "`$n`";
            }

            public function quote(mixed $v): string
            {
                return "'" . $v . "'";
            }

            public function setQuery(mixed $q): static
            {
                return $this;
            }

            public function execute(): void
            {
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function loadResult(): mixed
            {
                return null;
            }
        };

        return new class ($lessonItem, $db) extends LessonModel {
            public function __construct(
                private readonly object $lessonItem,
                private readonly object $fakeDb
            ) {
            }

            public function getItem($pk = null): object
            {
                return $this->lessonItem;
            }

            public function isAttendanceDateAllowed(?object $lesson, mixed $date): bool
            {
                return true;
            }

            public function getDatabase(): object
            {
                return $this->fakeDb;
            }
        };
    }
}
