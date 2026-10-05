<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required for loading the Admin SubscriptionModel class.
// Bracketed namespace syntax is required when mixing multiple namespace blocks.
// ---------------------------------------------------------------------------

namespace Joomla\CMS\MVC\Model {
    if (!class_exists('Joomla\\CMS\\MVC\\Model\\AdminModel')) {
        abstract class AdminModel
        {
            public function setError(string $error): void
            {
            }

            public function getError(): string
            {
                return '';
            }
        }
    }
}

namespace Joomla\CMS\Language {
    if (!class_exists('Joomla\\CMS\\Language\\Text')) {
        class Text
        {
            public static function _(string $string): string
            {
                return $string;
            }

            public static function sprintf(string $string, mixed ...$args): string
            {
                return $string;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel as AdminSubscriptionModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for admin SubscriptionModel::delete() — specifically the waitlist-promotion
     * trigger introduced in Release 1.3.25 (PR #97).
     *
     * Key regression risks:
     *  - When canDelete() returns false, the DELETE must not be issued and the method
     *    must return false.
     *  - When the deleted subscription is on the waiting list (subscribed=1), no
     *    waitlist promotion must be triggered.
     *  - When an enrolled subscription (subscribed=0) is deleted, promoteWaitingList()
     *    must be called with the correct lesson id so the oldest waiting-list student
     *    can be promoted automatically.
     *
     * @since  1.3.25
     */
    class AdminSubscriptionModelDeleteTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // Guard clauses — delete blocked
        // -----------------------------------------------------------------------

        /**
         * When getItem() returns nothing (falsy), delete() must return false without
         * issuing a DB DELETE and without calling promoteWaitingList().
         *
         * @return void
         */
        public function testDeleteReturnsFalseWhenGetItemReturnsNothing(): void
        {
            $db = $this->makeDeleteDb();
            $model = $this->makeModel(
                db: $db,
                getItemReturn: false,
                canDeleteReturn: true
            );

            $id = 1;
            $result = $model->delete($id);

            $this->assertFalse($result);
            $this->assertFalse($db->deleteCalled, 'No DB DELETE must be issued when getItem() fails');
            $this->assertNull($model->lastPromoteCallLessonId, 'promoteWaitingList() must not be called');
        }

        /**
         * When canDelete() returns false, delete() must return false without issuing
         * a DB DELETE or calling promoteWaitingList().
         *
         * @return void
         */
        public function testDeleteReturnsFalseWhenCanDeleteIsFalse(): void
        {
            $db = $this->makeDeleteDb();
            $model = $this->makeModel(
                db: $db,
                getItemReturn: $this->makeSubscription(subscribed: 0, lesson: 5),
                canDeleteReturn: false
            );

            $id = 1;
            $result = $model->delete($id);

            $this->assertFalse($result);
            $this->assertFalse($db->deleteCalled, 'No DB DELETE must be issued when canDelete() is false');
            $this->assertNull($model->lastPromoteCallLessonId, 'promoteWaitingList() must not be called');
        }

        // -----------------------------------------------------------------------
        // Waiting-list subscription — no promotion
        // -----------------------------------------------------------------------

        /**
         * Deleting a waiting-list subscription (subscribed=1) must issue the DB
         * DELETE but must NOT call promoteWaitingList() because no enrolled seat
         * was freed.
         *
         * @return void
         */
        public function testDeleteDoesNotPromoteWhenWaitingListSubscriptionDeleted(): void
        {
            $db = $this->makeDeleteDb();
            $model = $this->makeModel(
                db: $db,
                getItemReturn: $this->makeSubscription(subscribed: 1, lesson: 7),
                canDeleteReturn: true
            );

            $id = 1;
            $result = $model->delete($id);

            $this->assertTrue($result);
            $this->assertTrue($db->deleteCalled, 'DB DELETE must be issued for a waiting-list subscription');
            $this->assertNull(
                $model->lastPromoteCallLessonId,
                'promoteWaitingList() must NOT be called for a waiting-list subscription'
            );
        }

        // -----------------------------------------------------------------------
        // Enrolled subscription — promotion required
        // -----------------------------------------------------------------------

        /**
         * Deleting an enrolled subscription (subscribed=0) must issue the DB DELETE
         * and then call promoteWaitingList() with the correct lesson id so the oldest
         * waiting-list student can fill the freed seat.
         *
         * @return void
         */
        public function testDeletePromotesWaitlistWhenEnrolledSubscriptionDeleted(): void
        {
            $db = $this->makeDeleteDb();
            $model = $this->makeModel(
                db: $db,
                getItemReturn: $this->makeSubscription(subscribed: 0, lesson: 5),
                canDeleteReturn: true
            );

            $id = 1;
            $result = $model->delete($id);

            $this->assertTrue($result);
            $this->assertTrue($db->deleteCalled, 'DB DELETE must be issued for an enrolled subscription');
            $this->assertSame(
                5,
                $model->lastPromoteCallLessonId,
                'promoteWaitingList() must be called with the freed lesson id'
            );
            $this->assertSame(
                1,
                $model->lastPromoteCallLimit,
                'promoteWaitingList() must ask for exactly 1 promotion (one freed seat)'
            );
        }

        /**
         * Deleting an enrolled subscription with lesson id 0 must issue the DB DELETE
         * but must NOT call promoteWaitingList() (no lesson to promote from).
         *
         * @return void
         */
        public function testDeleteDoesNotPromoteWhenLessonIdIsZero(): void
        {
            $db = $this->makeDeleteDb();
            $model = $this->makeModel(
                db: $db,
                getItemReturn: $this->makeSubscription(subscribed: 0, lesson: 0),
                canDeleteReturn: true
            );

            $id = 1;
            $result = $model->delete($id);

            $this->assertTrue($result);
            $this->assertTrue($db->deleteCalled, 'DB DELETE must be issued even when lesson id is 0');
            $this->assertNull(
                $model->lastPromoteCallLessonId,
                'promoteWaitingList() must NOT be called when lesson id is 0'
            );
        }

        // -----------------------------------------------------------------------
        // Helpers — fixtures
        // -----------------------------------------------------------------------

        /**
         * Build a minimal subscription row for use in tests.
         *
         * @param   int  $subscribed  0 = enrolled, 1 = waiting list.
         * @param   int  $lesson      Lesson id.
         *
         * @return  object
         */
        private function makeSubscription(int $subscribed, int $lesson): object
        {
            return (object) [
                'id' => 1,
                'student' => 10,
                'lesson' => $lesson,
                'subscribed' => $subscribed,
            ];
        }

        // -----------------------------------------------------------------------
        // Helpers — model factory
        // -----------------------------------------------------------------------

        /**
         * Create an AdminSubscriptionModel stub with controllable getItem(), canDelete()
         * and a spy on promoteWaitingList().
         *
         * The stub records the last promoteWaitingList() call arguments via public
         * properties so tests can assert on them.
         *
         * @param   object        $db               Fake database.
         * @param   object|false  $getItemReturn    Return value of getItem().
         * @param   bool          $canDeleteReturn  Return value of canDelete().
         *
         * @return  AdminSubscriptionModel&object{lastPromoteCallLessonId:int|null,lastPromoteCallLimit:int|null}
         */
        private function makeModel(
            object $db,
            object|false $getItemReturn,
            bool $canDeleteReturn
        ): AdminSubscriptionModel {
            return new class ($db, $getItemReturn, $canDeleteReturn) extends AdminSubscriptionModel {
                public ?int $lastPromoteCallLessonId = null;
                public ?int $lastPromoteCallLimit = null;

                /** @var list<string> */
                private array $errors = [];

                public function __construct(
                    private readonly object $db,
                    private readonly object|false $getItemReturn,
                    private readonly bool $canDeleteReturn
                ) {
                }

                public function setError($error): void
                {
                    $this->errors[] = (string) $error;
                }

                public function getError($i = null, $toString = true): string
                {
                    return $this->errors[0] ?? '';
                }

                public function getItem($pk = null): object|false
                {
                    return $this->getItemReturn;
                }

                protected function canDelete($record): bool
                {
                    return $this->canDeleteReturn;
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function promoteWaitingList(int $lessonId, int $limit = 1): array
                {
                    $this->lastPromoteCallLessonId = $lessonId;
                    $this->lastPromoteCallLimit = $limit;

                    return [];
                }
            };
        }

        // -----------------------------------------------------------------------
        // Helpers — database stub
        // -----------------------------------------------------------------------

        /**
         * Build a minimal fake database for the delete() method.
         *
         * The DELETE query builder needs delete(), where(), and execute().
         * The stub records whether execute() was called in delete mode via $deleteCalled.
         *
         * @return  object{deleteCalled:bool}
         */
        private function makeDeleteDb(): object
        {
            $qb = new class {
                public string $mode = 'unknown';

                public function delete(mixed $table): static
                {
                    $this->mode = 'delete';

                    return $this;
                }

                public function where(mixed $cond): static
                {
                    return $this;
                }
            };

            return new class ($qb) {
                public bool $deleteCalled = false;
                private object $activeQuery;

                public function __construct(private readonly object $qb)
                {
                    $this->activeQuery = $qb;
                }

                public function getQuery(bool $new = true): object
                {
                    if ($new) {
                        $this->qb->mode = 'unknown';
                    }

                    $this->activeQuery = $this->qb;

                    return $this->qb;
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        return array_map(static fn($part) => '`' . $part . '`', $n);
                    }

                    return '`' . $n . '`';
                }

                public function setQuery(mixed $q): static
                {
                    $this->activeQuery = $q;

                    return $this;
                }

                public function execute(): bool
                {
                    if (($this->activeQuery->mode ?? '') === 'delete') {
                        $this->deleteCalled = true;
                    }

                    return true;
                }
            };
        }
    }
}
