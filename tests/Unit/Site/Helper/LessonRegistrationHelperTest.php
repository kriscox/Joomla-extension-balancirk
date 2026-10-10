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
}
