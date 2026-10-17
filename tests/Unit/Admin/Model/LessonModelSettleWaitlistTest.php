<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs — bracketed syntax required when multiple namespace blocks exist.
// Guards prevent re-declaration when the full suite runs AdminLessonModelStudentsTest first.
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

            public function validate($form, $data, $group = null)
            {
                return $data;
            }

            public function getState($property = null, $default = null): mixed
            {
                return $default;
            }
        }
    }
}

namespace Joomla\CMS\Language {
    if (!class_exists('Joomla\\CMS\\Language\\Text')) {
        class Text
        {
            public static function _($string): string
            {
                return $string;
            }

            public static function sprintf($string, mixed ...$args): string
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
    use CoCoCo\Component\Balancirk\Administrator\Model\LessonModel as AdminLessonModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for AdminLessonModel::settleWaitlist() and getWaitlistOrdered().
     *
     * settleWaitlist() key regression risks:
     * - Invalid lessonId (≤ 0) must be rejected before querying the database.
     * - A promote/dismiss overlap must be rejected as a conflict.
     * - Non-FIFO promotion must be rejected unless confirmFifoOverride is true.
     * - A missing lesson row must cause a hard failure.
     *
     * getWaitlistOrdered() key risk:
     * - With no resolvable lessonId the helper is called with 0 and returns [].
     * - With an explicit id the ranked rows are returned.
     *
     * @since  1.3.25
     */
    class LessonModelSettleWaitlistTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // settleWaitlist() — guard: invalid lessonId
        // -----------------------------------------------------------------------

        /**
         * lessonId = 0 must be rejected before any database call.
         *
         * @return void
         */
        public function testSettleWaitlistReturnsFalseForZeroLessonId(): void
        {
            $model = $this->makeModelWithoutDb();

            $this->assertFalse($model->settleWaitlist(0, [], []));
        }

        /**
         * lessonId < 0 must also be rejected before any database call.
         *
         * @return void
         */
        public function testSettleWaitlistReturnsFalseForNegativeLessonId(): void
        {
            $model = $this->makeModelWithoutDb();

            $this->assertFalse($model->settleWaitlist(-5, [], []));
        }

        // -----------------------------------------------------------------------
        // settleWaitlist() — guard: promote/dismiss overlap
        // -----------------------------------------------------------------------

        /**
         * When a subscription id appears in both promoteIds and dismissIds
         * the call must fail immediately as a conflict.
         *
         * @return void
         */
        public function testSettleWaitlistReturnsFalseWhenPromoteAndDismissOverlap(): void
        {
            $db = new \SettleWaitlistFakeDb([]);
            $model = $this->makeModelWithDb($db);

            // id 2 is in both lists
            $this->assertFalse($model->settleWaitlist(5, [1, 2], [2, 3]));
        }

        /**
         * A single duplicate id between promote and dismiss must trigger the guard.
         *
         * @return void
         */
        public function testSettleWaitlistReturnsFalseForMinimalOverlap(): void
        {
            $db = new \SettleWaitlistFakeDb([]);
            $model = $this->makeModelWithDb($db);

            $this->assertFalse($model->settleWaitlist(5, [7], [7]));
        }

        // -----------------------------------------------------------------------
        // settleWaitlist() — guard: FIFO prefix validation
        // -----------------------------------------------------------------------

        /**
         * Promoting a non-FIFO selection without confirmFifoOverride must fail.
         *
         * Waitlist FIFO order: subscription ids 10 → 20 → 30.
         * Promoting [10, 30] (skipping 20) is not a prefix → guard fires.
         *
         * @return void
         */
        public function testSettleWaitlistReturnsFalseWhenPromoteIdsNotFifoPrefixAndNoOverride(): void
        {
            $waitingRows = [
                (object) ['id' => 10, 'student' => 1, 'lesson' => 5,
                    'firstname' => 'A', 'name' => 'Z', 'birthdate' => '2012-01-01'],
                (object) ['id' => 20, 'student' => 2, 'lesson' => 5,
                    'firstname' => 'B', 'name' => 'Y', 'birthdate' => '2013-02-02'],
                (object) ['id' => 30, 'student' => 3, 'lesson' => 5,
                    'firstname' => 'C', 'name' => 'X', 'birthdate' => '2014-03-03'],
            ];
            $db = new \SettleWaitlistFakeDb($waitingRows);
            $model = $this->makeModelWithDb($db);

            // [10, 30] skips id 20 — not a FIFO prefix
            $this->assertFalse($model->settleWaitlist(5, [10, 30], [], false));
        }

        /**
         * Confirming the FIFO override bypasses the FIFO check.
         * The subsequent lesson-row load (returning null) triggers the lesson-not-found guard.
         *
         * Verifies that confirmFifoOverride=true actually skips the FIFO guard
         * and the error is 'JLIB_APPLICATION_ERROR_SAVE_FAILED' (lesson not found),
         * not 'COM_BALANCIRK_WAITLIST_SETTLE_FIFO_REQUIRED'.
         *
         * @return void
         */
        public function testSettleWaitlistBypassesFifoCheckWhenOverrideConfirmed(): void
        {
            $waitingRows = [
                (object) ['id' => 10, 'student' => 1, 'lesson' => 5,
                    'firstname' => 'A', 'name' => 'Z', 'birthdate' => '2012-01-01'],
                (object) ['id' => 20, 'student' => 2, 'lesson' => 5,
                    'firstname' => 'B', 'name' => 'Y', 'birthdate' => '2013-02-02'],
                (object) ['id' => 30, 'student' => 3, 'lesson' => 5,
                    'firstname' => 'C', 'name' => 'X', 'birthdate' => '2014-03-03'],
            ];
            // lessonRow = null so loadLessonRow() returns null after the FIFO check passes
            $db = new \SettleWaitlistFakeDb($waitingRows, null);
            $capturedErrors = [];
            $model = $this->makeModelCapturingErrors($db, $capturedErrors);

            // [10, 30] would normally fail the FIFO check — override=true skips it
            $result = $model->settleWaitlist(5, [10, 30], [], true);

            $this->assertFalse($result, 'Must fail on lesson-not-found after FIFO override');
            $this->assertNotContains(
                'COM_BALANCIRK_WAITLIST_SETTLE_FIFO_REQUIRED',
                $capturedErrors,
                'FIFO error must NOT be set when override is confirmed'
            );
            $this->assertContains(
                'JLIB_APPLICATION_ERROR_SAVE_FAILED',
                $capturedErrors,
                'Lesson-not-found error must be set after FIFO override'
            );
        }

        // -----------------------------------------------------------------------
        // settleWaitlist() — guard: lesson row not found
        // -----------------------------------------------------------------------

        /**
         * When the lesson row cannot be loaded the call must fail.
         *
         * An empty promote/dismiss selection is valid FIFO, so the code reaches
         * the loadLessonRow step. A null result from the DB triggers the guard.
         *
         * @return void
         */
        public function testSettleWaitlistReturnsFalseWhenLessonNotFound(): void
        {
            $waitingRows = [
                (object) ['id' => 10, 'student' => 1, 'lesson' => 5,
                    'firstname' => 'A', 'name' => 'Z', 'birthdate' => '2012-01-01'],
            ];
            $db = new \SettleWaitlistFakeDb($waitingRows, null);
            $model = $this->makeModelWithDb($db);

            // Empty promote + empty dismiss is FIFO-valid, proceeds to lesson load → null → false
            $this->assertFalse($model->settleWaitlist(5, [], []));
        }

        // -----------------------------------------------------------------------
        // getWaitlistOrdered() — delegation to WaitlistSettlementHelper
        // -----------------------------------------------------------------------

        /**
         * getWaitlistOrdered() must return the FIFO-ranked rows from the helper.
         *
         * @return void
         */
        public function testGetWaitlistOrderedReturnsFifoRankedRows(): void
        {
            $waitingRows = [
                (object) ['id' => 11, 'student' => 1, 'lesson' => 3,
                    'firstname' => 'A', 'name' => 'B', 'birthdate' => '2010-01-01'],
                (object) ['id' => 22, 'student' => 2, 'lesson' => 3,
                    'firstname' => 'C', 'name' => 'D', 'birthdate' => '2011-02-02'],
            ];
            $db = new \SettleWaitlistFakeDb($waitingRows);
            $model = $this->makeModelWithDb($db);

            $result = $model->getWaitlistOrdered(3);

            $this->assertCount(2, $result);
            $this->assertSame(1, $result[0]->fifo_rank);
            $this->assertSame(11, (int) $result[0]->subscription_id);
            $this->assertSame(2, $result[1]->fifo_rank);
            $this->assertSame(22, (int) $result[1]->subscription_id);
        }

        /**
         * getWaitlistOrdered() must return [] when no lessonId can be resolved.
         *
         * When getState('lesson.id') returns 0 and getItem() returns id=0,
         * WaitlistSettlementHelper::listWaitingOrdered(db, 0) is called and returns []
         * without executing any query.
         *
         * @return void
         */
        public function testGetWaitlistOrderedReturnsEmptyWhenNoLessonIdResolved(): void
        {
            $db = new \SettleWaitlistFakeDb([]);
            $model = new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getState($property = null, $default = null): mixed
                {
                    return 0;
                }

                public function getItem($pk = null): object
                {
                    return (object) ['id' => 0];
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }
            };

            $this->assertSame([], $model->getWaitlistOrdered(null));
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Model that must not query the database (safe for the lessonId=0 guard).
         *
         * @return AdminLessonModel
         */
        private function makeModelWithoutDb(): AdminLessonModel
        {
            return new class extends AdminLessonModel {
                public function __construct()
                {
                }

                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for this input');
                }
            };
        }

        /**
         * Model that forwards getDatabase() to an injected fake.
         *
         * @param   object  $db  Fake database instance.
         *
         * @return  AdminLessonModel
         */
        private function makeModelWithDb(object $db): AdminLessonModel
        {
            return new class ($db) extends AdminLessonModel {
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
         * Model that captures setError() calls for assertion.
         *
         * @param   object  $db             Fake database instance.
         * @param   array   &$capturedErrors Reference populated with error strings.
         *
         * @return  AdminLessonModel
         */
        private function makeModelCapturingErrors(object $db, array &$capturedErrors): AdminLessonModel
        {
            return new class ($db, $capturedErrors) extends AdminLessonModel {
                public function __construct(
                    private readonly object $db,
                    private array &$capturedErrors
                ) {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function setError(string $error): void
                {
                    $this->capturedErrors[] = $error;
                }
            };
        }
    }
}

// ---------------------------------------------------------------------------
// Fake database helpers — global namespace so they are accessible to the
// anonymous class constructors above.
// ---------------------------------------------------------------------------

namespace {
    if (!class_exists('SettleWaitlistFakeDb')) {
        /**
         * Minimal database fake for settleWaitlist / getWaitlistOrdered tests.
         *
         * loadObjectList() returns the pre-configured waiting-list rows (used by
         * WaitlistSettlementHelper::listWaitingOrdered).
         *
         * loadObject() returns the pre-configured lesson row, or null (used by
         * loadLessonRow inside AdminLessonModel::settleWaitlist).
         */
        final class SettleWaitlistFakeDb
        {
            /**
             * @param   object[]       $waitingRows  Rows returned by loadObjectList().
             * @param   object|null    $lessonRow    Object returned by loadObject() (null = not found).
             */
            public function __construct(
                private readonly array $waitingRows,
                private readonly ?object $lessonRow = null
            ) {
            }

            public function getQuery(bool $new = true): SettleWaitlistFakeQuery
            {
                return new SettleWaitlistFakeQuery();
            }

            public function quoteName(mixed $name, mixed $as = null): mixed
            {
                if (is_array($name)) {
                    return $name;
                }

                return (string) $name;
            }

            public function quote(mixed $value): string
            {
                return "'" . $value . "'";
            }

            public function setQuery(mixed $query): static
            {
                return $this;
            }

            public function loadObjectList(): array
            {
                return $this->waitingRows;
            }

            public function loadObject(): ?object
            {
                return $this->lessonRow;
            }

            public function execute(): bool
            {
                return true;
            }

            public function insertid(): int
            {
                return 0;
            }
        }
    }

    if (!class_exists('SettleWaitlistFakeQuery')) {
        /**
         * Minimal query-builder fake — absorbs all builder calls and returns self.
         */
        final class SettleWaitlistFakeQuery
        {
            public function select(mixed $columns): static
            {
                return $this;
            }

            public function from(mixed $table): static
            {
                return $this;
            }

            public function join(mixed $type, mixed $conditions, mixed ...$extra): static
            {
                return $this;
            }

            public function where(mixed $conditions): static
            {
                return $this;
            }

            public function order(mixed $order): static
            {
                return $this;
            }

            public function update(mixed $table): static
            {
                return $this;
            }

            public function delete(mixed $table): static
            {
                return $this;
            }

            public function set(mixed $conditions): static
            {
                return $this;
            }

            public function insert(mixed $table): static
            {
                return $this;
            }

            public function columns(mixed $columns): static
            {
                return $this;
            }

            public function values(mixed $values): static
            {
                return $this;
            }
        }
    }
}
