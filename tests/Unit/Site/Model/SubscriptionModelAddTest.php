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
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

/**
 * Tests for SubscriptionModel::add() guard clauses.
 *
 * add() guard: any call with a zero/negative studentId or lessonId must
 * return false immediately without touching the MVC factory or the database.
 * This prevents malformed data from reaching the DB layer and avoids fatal
 * errors when the factory is unavailable (CLI / unit-test context).
 *
 * @since  1.3.x
 */
class SubscriptionModelAddTest extends TestCase
{
    // -------------------------------------------------------------------------
    // add() — guard clauses (no DB/factory hit)
    // -------------------------------------------------------------------------

    /**
     * Null data must return false (both ids resolve to 0).
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddReturnsFalseForNullData(): void
    {
        $this->defineTextStub();

        $model = $this->makeAddGuardModel();

        $this->assertFalse($model->add(null));
    }

    /**
     * studentId = 0 must return false without calling getMVCFactory().
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddReturnsFalseForZeroStudentId(): void
    {
        $this->defineTextStub();

        $model = $this->makeAddGuardModel();

        $this->assertFalse($model->add(['student' => 0, 'lesson' => 5]));
    }

    /**
     * A negative studentId must return false without calling getMVCFactory().
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddReturnsFalseForNegativeStudentId(): void
    {
        $this->defineTextStub();

        $model = $this->makeAddGuardModel();

        $this->assertFalse($model->add(['student' => -3, 'lesson' => 5]));
    }

    /**
     * lessonId = 0 must return false without calling getMVCFactory().
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddReturnsFalseForZeroLessonId(): void
    {
        $this->defineTextStub();

        $model = $this->makeAddGuardModel();

        $this->assertFalse($model->add(['student' => 7, 'lesson' => 0]));
    }

    /**
     * A negative lessonId must return false without calling getMVCFactory().
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddReturnsFalseForNegativeLessonId(): void
    {
        $this->defineTextStub();

        $model = $this->makeAddGuardModel();

        $this->assertFalse($model->add(['student' => 7, 'lesson' => -1]));
    }


    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Define a minimal Text stub if not already defined.
     * Must only be called inside a #[RunInSeparateProcess] test method.
     *
     * @return void
     */
    private function defineTextStub(): void
    {
        if (!class_exists(\Joomla\CMS\Language\Text::class)) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            eval('namespace Joomla\\CMS\\Language; class Text { public static function _(string $k): string { return $k; } }');
        }
    }

    /**
     * Build a SubscriptionModel that throws if getMVCFactory() is called.
     * Used to verify the add() guard fires before any factory access.
     *
     * @return SubscriptionModel
     */
    private function makeAddGuardModel(): SubscriptionModel
    {
        return new class extends SubscriptionModel {
            public function __construct()
            {
            }
            public function setError(string $error): void
            {
            }
            public function getMVCFactory(): never
            {
                throw new \LogicException('getMVCFactory() must not be called when ids are invalid');
            }
        };
    }
}
