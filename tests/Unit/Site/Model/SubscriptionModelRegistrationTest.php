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
 * Tests for SubscriptionModel::isLessonOpenForRegistration().
 *
 * This private method guards lesson registration by checking:
 *  1. The lesson must be published (state === '1').
 *  2. Both start_registration and end_registration must be non-empty.
 *  3. Today's date must fall within [start_registration, end_registration].
 *
 * Registration eligibility is core business logic: a wrong answer silently
 * blocks valid sign-ups or allows sign-ups to closed lessons.
 *
 * @since  1.3.21
 */
class SubscriptionModelRegistrationTest extends TestCase
{
    private SubscriptionModel $model;
    private ReflectionMethod $method;

    protected function setUp(): void
    {
        $this->model = new class extends SubscriptionModel {
            public function __construct()
            {
            }
        };

        $this->method = new ReflectionMethod(SubscriptionModel::class, 'isLessonOpenForRegistration');
        $this->method->setAccessible(true);
    }

    // -------------------------------------------------------------------------
    // State checks
    // -------------------------------------------------------------------------

    /**
     * A lesson with state '0' (unpublished) must not be open for registration.
     *
     * @return void
     */
    public function testUnpublishedLessonIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '0',
            'start_registration' => '2000-01-01',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    /**
     * A lesson with state '2' (archived) must not be open for registration.
     *
     * @return void
     */
    public function testArchivedLessonIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '2',
            'start_registration' => '2000-01-01',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    /**
     * A lesson with a null state must not be open for registration.
     *
     * @return void
     */
    public function testNullStateLessonIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => null,
            'start_registration' => '2000-01-01',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    // -------------------------------------------------------------------------
    // Missing date fields
    // -------------------------------------------------------------------------

    /**
     * A published lesson with an empty start_registration must not be open.
     *
     * @return void
     */
    public function testMissingStartRegistrationIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '1',
            'start_registration' => '',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    /**
     * A published lesson with an empty end_registration must not be open.
     *
     * @return void
     */
    public function testMissingEndRegistrationIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '1',
            'start_registration' => '2000-01-01',
            'end_registration'   => '',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    /**
     * A published lesson with both registration dates missing must not be open.
     *
     * @return void
     */
    public function testMissingBothDatesIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '1',
            'start_registration' => '',
            'end_registration'   => '',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    // -------------------------------------------------------------------------
    // Date range checks
    // -------------------------------------------------------------------------

    /**
     * A published lesson whose registration window clearly covers today must be open.
     *
     * @return void
     */
    public function testPublishedLessonWithOpenWindowIsOpen(): void
    {
        $lesson = (object)[
            'state'              => '1',
            'start_registration' => '2000-01-01',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertTrue($this->invoke($lesson));
    }

    /**
     * A published lesson whose registration window ended in the past must not be open.
     *
     * @return void
     */
    public function testPublishedLessonWithExpiredWindowIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '1',
            'start_registration' => '2000-01-01',
            'end_registration'   => '2000-01-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    /**
     * A published lesson whose registration window starts in the future must not be open.
     *
     * @return void
     */
    public function testPublishedLessonWithFutureWindowIsNotOpen(): void
    {
        $lesson = (object)[
            'state'              => '1',
            'start_registration' => '2099-01-01',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    // -------------------------------------------------------------------------
    // Missing optional properties
    // -------------------------------------------------------------------------

    /**
     * If the lesson object has no state property, it must not be open.
     *
     * @return void
     */
    public function testLessonWithNoStatePropertyIsNotOpen(): void
    {
        $lesson = (object)[
            'start_registration' => '2000-01-01',
            'end_registration'   => '2099-12-31',
        ];

        $this->assertFalse($this->invoke($lesson));
    }

    /**
     * If the lesson object has no registration date properties, it must not be open.
     *
     * @return void
     */
    public function testLessonWithNoDatePropertiesIsNotOpen(): void
    {
        $lesson = (object)['state' => '1'];

        $this->assertFalse($this->invoke($lesson));
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Invoke the private isLessonOpenForRegistration() via reflection.
     *
     * @param   object  $lesson  Lesson record to test.
     *
     * @return  bool
     */
    private function invoke(object $lesson): bool
    {
        return $this->method->invoke($this->model, $lesson);
    }
}
