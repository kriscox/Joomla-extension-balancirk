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
 * Tests for SubscriptionModel::add() guard clauses and delete().
 *
 * add() guard: any call with a zero/negative studentId or lessonId must
 * return false immediately without touching the MVC factory or the database.
 * This prevents malformed data from reaching the DB layer and avoids fatal
 * errors when the factory is unavailable (CLI / unit-test context).
 *
 * delete(): always returns true after executing a DELETE query; verifies that
 * the correct student and lesson ids appear in the WHERE clause.
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
    // delete() — DB-backed DELETE query
    // -------------------------------------------------------------------------

    /**
     * delete() must always return true after executing the query.
     *
     * @return void
     */
    public function testDeleteAlwaysReturnsTrue(): void
    {
        $pks = ['student' => 3, 'lesson' => 7];
        $model = $this->makeDeleteModel($pks);

        $this->assertTrue($model->delete($pks));
    }

    /**
     * The DELETE WHERE clause must include the student id.
     *
     * @return void
     */
    public function testDeleteWhereClauseIncludesStudentId(): void
    {
        $capturedWhere = [];
        $pks = ['student' => 11, 'lesson' => 22];
        $model = $this->makeDeleteModelCapturingWhere($capturedWhere);

        $model->delete($pks);

        $combined = implode(' ', $capturedWhere);
        $this->assertStringContainsString('11', $combined);
    }

    /**
     * The DELETE WHERE clause must include the lesson id.
     *
     * @return void
     */
    public function testDeleteWhereClauseIncludesLessonId(): void
    {
        $capturedWhere = [];
        $pks = ['student' => 11, 'lesson' => 22];
        $model = $this->makeDeleteModelCapturingWhere($capturedWhere);

        $model->delete($pks);

        $combined = implode(' ', $capturedWhere);
        $this->assertStringContainsString('22', $combined);
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

    /**
     * Build a SubscriptionModel whose DB stub always succeeds for delete().
     *
     * @param   array  $pks  The same pks that will be passed to delete().
     *
     * @return  SubscriptionModel
     */
    private function makeDeleteModel(array $pks): SubscriptionModel
    {
        $capturedWhere = [];
        return $this->makeDeleteModelCapturingWhere($capturedWhere);
    }

    /**
     * Build a SubscriptionModel whose query builder captures WHERE clauses.
     *
     * @param   array  $capturedWhere  Reference array populated with WHERE strings.
     *
     * @return  SubscriptionModel
     */
    private function makeDeleteModelCapturingWhere(array &$capturedWhere): SubscriptionModel
    {
        $qb = new class ($capturedWhere) {
            public function __construct(private array &$captured)
            {
            }
            public function delete(mixed $t): static
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
            public function quoteName(mixed $n, mixed $a = null): string
            {
                return "`$n`";
            }
            public function setQuery(mixed $q): static
            {
                return $this;
            }
            public function execute(): bool
            {
                return true;
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
