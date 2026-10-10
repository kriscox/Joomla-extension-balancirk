<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\LessonRegistrationHelper;
use PHPUnit\Framework\TestCase;

final class LessonRegistrationHelperTest extends TestCase
{
    public function testIsOpenForSubscriptionRequiresCurrentStateAndOpenWindow(): void
    {
        $lesson = (object) [
            'state' => '1',
            'registration_closed' => 0,
            'start_registration' => '2026-01-01',
            'end_registration' => '2026-12-31',
        ];

        $this->assertTrue(LessonRegistrationHelper::isOpenForSubscription($lesson, '2026-06-01'));
    }

    public function testIsOpenForSubscriptionFailsWhenClosed(): void
    {
        $lesson = (object) [
            'state' => '1',
            'registration_closed' => 1,
            'start_registration' => '2026-01-01',
            'end_registration' => '2026-12-31',
        ];

        $this->assertFalse(LessonRegistrationHelper::isOpenForSubscription($lesson, '2026-06-01'));
    }

    public function testIsOpenForSubscriptionFailsWhenCancelled(): void
    {
        $lesson = (object) [
            'state' => '-1',
            'registration_closed' => 0,
            'start_registration' => '2026-01-01',
            'end_registration' => '2026-12-31',
        ];

        $this->assertFalse(LessonRegistrationHelper::isOpenForSubscription($lesson, '2026-06-01'));
        $this->assertTrue(LessonRegistrationHelper::isCancelled($lesson));
    }

    public function testIsFullWhenClosedOrAtCapacity(): void
    {
        $this->assertTrue(LessonRegistrationHelper::isFull((object) [
            'registration_closed' => 1,
            'numberOfStudents' => 5,
            'max_students' => 12,
        ]));

        $this->assertTrue(LessonRegistrationHelper::isFull((object) [
            'registration_closed' => 0,
            'numberOfStudents' => 12,
            'max_students' => 12,
        ]));

        $this->assertFalse(LessonRegistrationHelper::isFull((object) [
            'registration_closed' => 0,
            'numberOfStudents' => 5,
            'max_students' => 12,
        ]));
    }

    public function testIsFullWithUnlimitedCapacityIsNeverFull(): void
    {
        $this->assertFalse(LessonRegistrationHelper::isFull((object) [
            'registration_closed' => 0,
            'numberOfStudents' => 100,
            'max_students' => 0,
        ]));
    }

    public function testIsOpenForSubscriptionFailsWhenRegistrationDatesAreMissing(): void
    {
        $base = [
            'state' => '1',
            'registration_closed' => 0,
        ];

        $this->assertFalse(
            LessonRegistrationHelper::isOpenForSubscription(
                (object) array_merge($base, ['start_registration' => '', 'end_registration' => '2026-12-31']),
                '2026-06-01'
            )
        );

        $this->assertFalse(
            LessonRegistrationHelper::isOpenForSubscription(
                (object) array_merge($base, ['start_registration' => '2026-01-01', 'end_registration' => '']),
                '2026-06-01'
            )
        );

        $this->assertFalse(
            LessonRegistrationHelper::isOpenForSubscription(
                (object) $base,
                '2026-06-01'
            )
        );
    }

    public function testCloseRegistrationReturnsFalseForInvalidId(): void
    {
        $db = new RegistrationFakeDb();
        $this->assertFalse(LessonRegistrationHelper::closeRegistration($db, 0));
        $this->assertFalse(LessonRegistrationHelper::closeRegistration($db, -1));
    }

    public function testReopenRegistrationReturnsFalseForInvalidId(): void
    {
        $db = new RegistrationFakeDb();
        $this->assertFalse(LessonRegistrationHelper::reopenRegistration($db, 0));
    }

    public function testCloseRegistrationExecutesUpdateAndReturnsTrue(): void
    {
        $db = new RegistrationFakeDb();
        $result = LessonRegistrationHelper::closeRegistration($db, 12);

        $this->assertTrue($result);
        $this->assertTrue($db->updateExecuted);
        $this->assertStringContainsString('registration_closed = 1', $db->lastSetCondition);
    }

    public function testReopenRegistrationExecutesUpdateWithZeroFlag(): void
    {
        $db = new RegistrationFakeDb();
        $result = LessonRegistrationHelper::reopenRegistration($db, 12);

        $this->assertTrue($result);
        $this->assertTrue($db->updateExecuted);
        $this->assertStringContainsString('registration_closed = 0', $db->lastSetCondition);
    }
}

/**
 * Minimal fluent query builder stub for registration open/close tests.
 */
final class RegistrationFakeQuery
{
    public string $lastSet = '';

    public function update($table): self
    {
        return $this;
    }

    public function set($conditions): self
    {
        $this->lastSet = (string) $conditions;
        return $this;
    }

    public function where($conditions): self
    {
        return $this;
    }
}

/**
 * Minimal database stub for LessonRegistrationHelper close/reopen tests.
 */
final class RegistrationFakeDb
{
    public bool $updateExecuted = false;
    public string $lastSetCondition = '';

    private ?RegistrationFakeQuery $activeQuery = null;

    public function getQuery(bool $new = true): RegistrationFakeQuery
    {
        return $this->activeQuery = new RegistrationFakeQuery();
    }

    /**
     * @param   string|string[]  $name  Column or table name.
     * @param   string|null      $as    Alias (ignored).
     *
     * @return  string|string[]
     */
    public function quoteName($name, $as = null)
    {
        if (is_array($name)) {
            return $name;
        }

        return (string) $name;
    }

    public function setQuery(mixed $query, int $offset = 0, int $limit = 0): self
    {
        if ($query instanceof RegistrationFakeQuery) {
            $this->activeQuery = $query;
        }

        return $this;
    }

    public function execute(): bool
    {
        $this->updateExecuted = true;

        if ($this->activeQuery !== null) {
            $this->lastSetCondition = $this->activeQuery->lastSet;
        }

        return true;
    }
}
