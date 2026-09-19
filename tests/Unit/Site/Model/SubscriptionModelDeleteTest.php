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

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
    abstract class JoomlaAdminModelSubscriptionDeleteStub
    {
        public function setError(string $error): void
        {
        }

        public function getError(): string
        {
            return '';
        }

        public function getState($property = null, $default = null): mixed
        {
            return $default;
        }
    }

    class_alias(
        \CoCoCo\Component\Balancirk\Tests\Unit\Site\Model\JoomlaAdminModelSubscriptionDeleteStub::class,
        \Joomla\CMS\MVC\Model\AdminModel::class
    );
}

/**
 * Tests for SubscriptionModel::countPresences(), getSubscriptionRecord(), and delete()
 * introduced in 1.3.20.
 *
 * @since  1.3.20
 */
class SubscriptionModelDeleteTest extends TestCase
{
    // -------------------------------------------------------------------------
    // countPresences() — guard clauses (no DB required)
    // -------------------------------------------------------------------------

    /**
     * countPresences() must return 0 when studentId is zero.
     *
     * @return void
     */
    public function testCountPresencesReturnsZeroWhenStudentIdIsZero(): void
    {
        $model = $this->makeGuardModel();

        $this->assertSame(0, $model->countPresences(0, 5));
    }

    /**
     * countPresences() must return 0 when studentId is negative.
     *
     * @return void
     */
    public function testCountPresencesReturnsZeroWhenStudentIdIsNegative(): void
    {
        $model = $this->makeGuardModel();

        $this->assertSame(0, $model->countPresences(-1, 5));
    }

    /**
     * countPresences() must return 0 when lessonId is zero.
     *
     * @return void
     */
    public function testCountPresencesReturnsZeroWhenLessonIdIsZero(): void
    {
        $model = $this->makeGuardModel();

        $this->assertSame(0, $model->countPresences(5, 0));
    }

    /**
     * countPresences() must return 0 when lessonId is negative.
     *
     * @return void
     */
    public function testCountPresencesReturnsZeroWhenLessonIdIsNegative(): void
    {
        $model = $this->makeGuardModel();

        $this->assertSame(0, $model->countPresences(5, -3));
    }

    // -------------------------------------------------------------------------
    // getSubscriptionRecord() — guard clauses (no DB required)
    // -------------------------------------------------------------------------

    /**
     * getSubscriptionRecord() must return null when id is zero.
     *
     * @return void
     */
    public function testGetSubscriptionRecordReturnsNullForZeroId(): void
    {
        $model = $this->makeGuardModel();

        $this->assertNull($model->getSubscriptionRecord(0));
    }

    /**
     * getSubscriptionRecord() must return null when id is negative.
     *
     * @return void
     */
    public function testGetSubscriptionRecordReturnsNullForNegativeId(): void
    {
        $model = $this->makeGuardModel();

        $this->assertNull($model->getSubscriptionRecord(-99));
    }

    // -------------------------------------------------------------------------
    // delete() — guard clauses (DB required for query build, but not execute)
    // -------------------------------------------------------------------------

    /**
     * delete() with a student/lesson pair must return false when studentId is zero.
     *
     * @return void
     */
    public function testDeleteReturnsFalseWhenStudentIdIsZero(): void
    {
        $model = $this->makeModelWithDb($this->makeDeleteDb());
        $pks = ['student' => 0, 'lesson' => 5];

        $this->assertFalse($model->delete($pks));
    }

    /**
     * delete() with a student/lesson pair must return false when lessonId is zero.
     *
     * @return void
     */
    public function testDeleteReturnsFalseWhenLessonIdIsZero(): void
    {
        $model = $this->makeModelWithDb($this->makeDeleteDb());
        $pks = ['student' => 5, 'lesson' => 0];

        $this->assertFalse($model->delete($pks));
    }

    /**
     * delete() with an integer id of zero must return false.
     *
     * @return void
     */
    public function testDeleteReturnsFalseWhenIntegerIdIsZero(): void
    {
        $model = $this->makeModelWithDb($this->makeDeleteDb());
        $pks = 0;

        $this->assertFalse($model->delete($pks));
    }

    /**
     * delete() with an array containing id zero must return false.
     *
     * @return void
     */
    public function testDeleteReturnsFalseWhenArrayIdIsZero(): void
    {
        $model = $this->makeModelWithDb($this->makeDeleteDb());
        $pks = ['id' => 0];

        $this->assertFalse($model->delete($pks));
    }

    // -------------------------------------------------------------------------
    // delete() — valid paths: WHERE clause verification
    // -------------------------------------------------------------------------

    /**
     * delete() with a valid student/lesson pair must execute a DELETE WHERE student AND lesson.
     *
     * @return void
     */
    public function testDeleteByStudentLessonPairFiltersOnStudentAndLesson(): void
    {
        $whereClauses = [];
        $executed = false;
        $db = $this->makeDeleteDb(whereClauses: $whereClauses, executed: $executed);

        $model = $this->makeModelWithDb($db);
        $pks = ['student' => 7, 'lesson' => 3];

        $this->assertTrue($model->delete($pks));
        $this->assertTrue($executed, 'Query must be executed for a valid student/lesson pair');

        $combined = implode(' ', $whereClauses);
        $this->assertStringContainsString('7', $combined);
        $this->assertStringContainsString('3', $combined);
    }

    /**
     * delete() with an integer subscription id must execute a DELETE WHERE id.
     *
     * @return void
     */
    public function testDeleteByIntegerIdFiltersOnId(): void
    {
        $whereClauses = [];
        $executed = false;
        $db = $this->makeDeleteDb(whereClauses: $whereClauses, executed: $executed);

        $model = $this->makeModelWithDb($db);
        $pks = 42;

        $this->assertTrue($model->delete($pks));
        $this->assertTrue($executed, 'Query must be executed for a valid subscription id');
        $this->assertStringContainsString('42', implode(' ', $whereClauses));
    }

    /**
     * delete() with an array containing a valid id key must execute a DELETE WHERE id.
     *
     * @return void
     */
    public function testDeleteByArrayIdKeyFiltersOnId(): void
    {
        $whereClauses = [];
        $executed = false;
        $db = $this->makeDeleteDb(whereClauses: $whereClauses, executed: $executed);

        $model = $this->makeModelWithDb($db);
        $pks = ['id' => 99];

        $this->assertTrue($model->delete($pks));
        $this->assertTrue($executed, 'Query must be executed for a valid array id');
        $this->assertStringContainsString('99', implode(' ', $whereClauses));
    }

    /**
     * Deleting an enrolled row must request one waitlist promotion.
     *
     * @return void
     */
    public function testDeletePromotesWhenEnrolledRowIsRemoved(): void
    {
        $promoteCalls = [];
        $row = (object) ['id' => 42, 'student' => 7, 'lesson' => 3, 'subscribed' => 0];
        $db = $this->makeDeleteDb(subscription: $row);
        $model = $this->makePromotingModel($db, $promoteCalls);
        $pks = 42;

        $this->assertTrue($model->delete($pks));
        $this->assertSame([[3, 1]], $promoteCalls);
    }

    /**
     * Deleting a waiting-list row must not promote anyone.
     *
     * @return void
     */
    public function testDeleteDoesNotPromoteWhenWaitingListRowIsRemoved(): void
    {
        $promoteCalls = [];
        $row = (object) ['id' => 42, 'student' => 7, 'lesson' => 3, 'subscribed' => 1];
        $db = $this->makeDeleteDb(subscription: $row);
        $model = $this->makePromotingModel($db, $promoteCalls);
        $pks = 42;

        $this->assertTrue($model->delete($pks));
        $this->assertSame([], $promoteCalls);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a SubscriptionModel stub without a database (for guard tests that
     * return before touching the DB).
     *
     * @return SubscriptionModel
     */
    private function makeGuardModel(): SubscriptionModel
    {
        return new class extends SubscriptionModel {
        };
    }

    /**
     * Create a SubscriptionModel stub that delegates getDatabase() to the given DB.
     *
     * @param   object  $db  Fake database.
     *
     * @return  SubscriptionModel
     */
    private function makeModelWithDb(object $db): SubscriptionModel
    {
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

    /**
     * Create a SubscriptionModel that records promoteWaitingList() calls.
     *
     * @param   object  $db            Fake database.
     * @param   array   &$promoteCalls Receives [lessonId, limit] pairs.
     *
     * @return  SubscriptionModel
     */
    private function makePromotingModel(object $db, array &$promoteCalls): SubscriptionModel
    {
        return new class ($db, $promoteCalls) extends SubscriptionModel {
            public function __construct(
                private readonly object $db,
                private array &$promoteCalls
            ) {
            }

            public function getDatabase(): object
            {
                return $this->db;
            }

            public function promoteWaitingList(int $lessonId, int $limit = 1): array
            {
                $this->promoteCalls[] = [$lessonId, $limit];

                return [];
            }
        };
    }

    /**
     * Build a fake DB sufficient for delete() queries.
     *
     * The query builder captures WHERE clauses; $executed is set to true on execute().
     *
     * @param   array  &$whereClauses  Receives WHERE clause strings.
     * @param   bool   &$executed      Set to true when execute() is called.
     *
     * @return  object
     */
    private function makeDeleteDb(
        array &$whereClauses = [],
        bool &$executed = false,
        ?object $subscription = null
    ): object {
        $qb = new class ($whereClauses) {
            public function __construct(private array &$whereClauses)
            {
            }

            public function select(mixed $c): static
            {
                return $this;
            }

            public function from(mixed $t): static
            {
                return $this;
            }

            public function update(mixed $t): static
            {
                return $this;
            }

            public function set(mixed $s): static
            {
                return $this;
            }

            public function order(mixed $o): static
            {
                return $this;
            }

            public function delete(mixed $t): static
            {
                return $this;
            }

            public function where(mixed $cond): static
            {
                $this->whereClauses[] = (string) $cond;

                return $this;
            }
        };

        return new class ($qb, $executed, $subscription) {
            public function __construct(
                private readonly object $qb,
                private bool &$executed,
                private readonly ?object $subscription
            ) {
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

            public function setQuery(mixed $q, int $offset = 0, int $limit = 0): static
            {
                return $this;
            }

            public function execute(): void
            {
                $this->executed = true;
            }

            public function loadObject(): ?object
            {
                return $this->subscription;
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
    }
}
