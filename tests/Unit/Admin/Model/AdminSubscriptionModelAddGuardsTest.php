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

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel as AdminSubscriptionModel;
    use PHPUnit\Framework\Attributes\PreserveGlobalState;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for admin SubscriptionModel::add() guards not covered by the age-override test.
     *
     * Covers:
     *  - Rejection when the lesson belongs to a past school year (new guard added 1.3.24).
     *  - Rejection when the student is already enrolled or waiting.
     *  - Successful enrolment directly when capacity is available.
     *  - Successful enrolment on the waiting list when the lesson is full.
     *
     * @since  1.3.24
     */
    class AdminSubscriptionModelAddGuardsTest extends TestCase
    {
        /**
         * Past-year lesson must be rejected with YEAR_NOT_ALLOWED before any
         * eligibility or subscription check is made.
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddRejectsPastYearLesson(): void
        {
            $this->defineStubs();

            $pastLesson = $this->makePastLesson();
            $db = $this->makeDatabase($pastLesson, null);
            $model = $this->makeModel($db);

            $result = $model->add(['student' => 3, 'lesson' => 5]);

            $this->assertFalse($result);
            $this->assertStringContainsString(
                'COM_BALANCIRK_SUBSCRIPTION_YEAR_NOT_ALLOWED',
                $model->getError()
            );
            $this->assertFalse($db->insertCalled, 'No INSERT must occur for a past-year lesson');
        }

        /**
         * When the student is already enrolled or on the waiting list, add() must
         * refuse with ALREADY_EXISTS and make no INSERT.
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddRejectsAlreadyEnrolledStudent(): void
        {
            $this->defineStubs();

            $lesson = $this->makeFutureLesson();
            $student = $this->makeEligibleStudent();
            // loadResultSequence: [1] → subscriptionExists() returns true on first loadResult()
            $db = $this->makeDatabase($lesson, $student, loadResultSequence: [1]);
            $model = $this->makeModel($db);

            $result = $model->add(['student' => 7, 'lesson' => 5]);

            $this->assertFalse($result);
            $this->assertStringContainsString(
                'COM_BALANCIRK_SUBSCRIPTION_ALREADY_EXISTS',
                $model->getError()
            );
            $this->assertFalse($db->insertCalled, 'No INSERT must occur when subscription already exists');
        }

        /**
         * When capacity is available, add() must INSERT with subscribed = 0
         * (directly enrolled).
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddEnrolsDirectlyWhenCapacityAvailable(): void
        {
            $this->defineStubs();

            $lesson = $this->makeFutureLesson(maxStudents: 20);
            $student = $this->makeEligibleStudent();
            // loadResultSequence: [null, 5] → subscriptionExists()=false, countLessonSubscriptions()=5
            // 5 < 20 → subscribed = 0 (directly enrolled)
            $db = $this->makeDatabase($lesson, $student, loadResultSequence: [null, 5]);
            $model = $this->makeModel($db);

            $result = $model->add(['student' => 7, 'lesson' => 5]);

            $this->assertTrue($result);
            $this->assertTrue($db->insertCalled, 'An INSERT must be made for the new subscription');
            $this->assertSame(0, $db->insertedSubscribed, 'subscribed must be 0 when capacity is available');
        }

        /**
         * When the lesson is full, add() must INSERT with subscribed = 1
         * (waiting list).
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddAddsToWaitingListWhenLessonFull(): void
        {
            $this->defineStubs();

            $lesson = $this->makeFutureLesson(maxStudents: 10);
            $student = $this->makeEligibleStudent();
            // loadResultSequence: [null, 10] → subscriptionExists()=false, countLessonSubscriptions()=10
            // 10 >= 10 → subscribed = 1 (waiting list)
            $db = $this->makeDatabase($lesson, $student, loadResultSequence: [null, 10]);
            $model = $this->makeModel($db);

            $result = $model->add(['student' => 7, 'lesson' => 5]);

            $this->assertTrue($result);
            $this->assertTrue($db->insertCalled, 'An INSERT must be made for the waiting-list subscription');
            $this->assertSame(1, $db->insertedSubscribed, 'subscribed must be 1 when lesson is full');
        }

        // -----------------------------------------------------------------------
        // Helpers — stubs
        // -----------------------------------------------------------------------

        /**
         * Define Text and ComponentHelper stubs for an isolated process.
         * Must be called inside a #[RunInSeparateProcess] test method.
         *
         * @return void
         */
        private function defineStubs(): void
        {
            if (!class_exists(\Joomla\CMS\Language\Text::class)) {
                // phpcs:ignore Squiz.PHP.Eval.Discouraged
                eval(
                    'namespace Joomla\\CMS\\Language;'
                    . ' class Text {'
                    . ' public static function _(string $k): string { return $k; }'
                    . ' public static function sprintf(string $k, mixed ...$a): string { return $k; }'
                    . ' }'
                );
            }

            if (!class_exists(\Joomla\CMS\Component\ComponentHelper::class)) {
                // phpcs:ignore Squiz.PHP.Eval.Discouraged
                eval(
                    'namespace Joomla\\CMS\\Component;'
                    . ' class ComponentHelper {'
                    . ' public static function getParams(string $e): object {'
                    . '   return new class { public function get(string $k, mixed $d = null): mixed { return $d; } };'
                    . ' } }'
                );
            }
        }

        // -----------------------------------------------------------------------
        // Helpers — lesson / student fixtures
        // -----------------------------------------------------------------------

        /**
         * Build a lesson that belongs to a past school year (2020).
         *
         * @return object
         */
        private function makePastLesson(): object
        {
            return (object) [
                'id' => 5,
                'year' => 2020,
                'state' => '1',
                'start' => '2020-09-01',
                'end' => '2021-06-30',
                'start_registration' => '2020-01-01',
                'end_registration' => '2021-12-31',
                'min_age' => null,
                'max_age' => null,
                'max_students' => 20,
                'name' => 'Past lesson',
            ];
        }

        /**
         * Build a future lesson whose age band covers any student born after 2010.
         *
         * @param   int  $maxStudents  Maximum capacity for this lesson.
         *
         * @return object
         */
        private function makeFutureLesson(int $maxStudents = 20): object
        {
            return (object) [
                'id' => 5,
                'year' => 2099,
                'state' => '1',
                'start' => '2099-09-01',
                'end' => '2100-06-30',
                'start_registration' => '2099-01-01',
                'end_registration' => '2099-12-31',
                'min_age' => null,
                'max_age' => null,
                'max_students' => $maxStudents,
                'name' => 'Future lesson',
            ];
        }

        /**
         * Build a student whose age falls within an unrestricted lesson.
         *
         * @return object
         */
        private function makeEligibleStudent(): object
        {
            return (object) [
                'id' => 7,
                'birthdate' => '2015-06-01',
                'firstname' => 'Jan',
                'name' => 'Janssen',
            ];
        }

        // -----------------------------------------------------------------------
        // Helpers — model factory
        // -----------------------------------------------------------------------

        /**
         * Create an AdminSubscriptionModel with a fake database and staff create rights.
         *
         * @param   object  $db  Fake database instance.
         *
         * @return  AdminSubscriptionModel
         */
        private function makeModel(object $db): AdminSubscriptionModel
        {
            return new class ($db) extends AdminSubscriptionModel {
                /** @var list<string> */
                private array $errors = [];

                public function __construct(private readonly object $db)
                {
                }

                public function setError($error): void
                {
                    $this->errors[] = (string) $error;
                }

                public function getError($i = null, $toString = true): string
                {
                    return $this->errors[0] ?? '';
                }

                public function canCreate(): bool
                {
                    return true;
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }
            };
        }

        // -----------------------------------------------------------------------
        // Helpers — database stub
        // -----------------------------------------------------------------------

        /**
         * Build a database stub that routes queries by the FROM table name.
         *
         * loadObject() returns the lesson when FROM contains 'lessons', the student
         * when FROM contains 'students', and null otherwise.
         *
         * loadResult() drains $loadResultSequence in order (first call = first element),
         * returning null when the sequence is exhausted.  This covers:
         *   - subscriptionExists() → first loadResult() call
         *   - countLessonSubscriptions() → second loadResult() call
         *
         * When an INSERT is executed, insertCalled is set to true and insertedSubscribed
         * is parsed from the VALUES string ("studentId,lessonId,subscribed").
         *
         * @param   object        $lesson              Lesson row returned by loadObject().
         * @param   object|null   $student             Student row returned by loadObject() (or null).
         * @param   list<mixed>   $loadResultSequence  Ordered return values for loadResult().
         *
         * @return  object
         */
        private function makeDatabase(
            object $lesson,
            ?object $student,
            array $loadResultSequence = []
        ): object {
            $qb = new class {
                public string $from = '';
                public string $mode = 'select';
                public string $insertedValues = '';

                public function select(mixed $x): static
                {
                    $this->mode = 'select';

                    return $this;
                }

                public function insert(mixed $t): static
                {
                    $this->mode = 'insert';
                    $this->from = (string) $t;

                    return $this;
                }

                public function columns(mixed $c): static
                {
                    return $this;
                }

                public function values(mixed $v): static
                {
                    $this->insertedValues = (string) $v;

                    return $this;
                }

                public function from(mixed $t): static
                {
                    $this->from = (string) $t;

                    return $this;
                }

                public function join(mixed ...$a): static
                {
                    return $this;
                }

                public function where(mixed $c): static
                {
                    return $this;
                }
            };

            return new class ($qb, $lesson, $student, $loadResultSequence) {
                public bool $insertCalled = false;
                public int $insertedSubscribed = -1;
                private object $activeQuery;
                private int $loadResultIndex = 0;

                public function __construct(
                    private readonly object $qb,
                    private readonly object $lesson,
                    private readonly ?object $student,
                    private readonly array $loadResultSequence
                ) {
                    $this->activeQuery = $qb;
                }

                public function getQuery(bool $new = true): object
                {
                    if ($new) {
                        $this->qb->from = '';
                        $this->qb->mode = 'select';
                        $this->qb->insertedValues = '';
                    }

                    $this->activeQuery = $this->qb;

                    return $this->qb;
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        return array_map(static fn($part) => '`' . $part . '`', $n);
                    }

                    $name = '`' . $n . '`';

                    if ($a !== null) {
                        return $name . ' AS `' . $a . '`';
                    }

                    return $name;
                }

                public function quote(mixed $value): string
                {
                    return "'" . $value . "'";
                }

                public function setQuery(mixed $q): static
                {
                    $this->activeQuery = $q;

                    return $this;
                }

                public function loadObject(): ?object
                {
                    $from = (string) ($this->activeQuery->from ?? '');

                    if (str_contains($from, 'lessons')) {
                        return $this->lesson;
                    }

                    if (str_contains($from, 'students')) {
                        return $this->student;
                    }

                    return null;
                }

                public function loadResult(): mixed
                {
                    return $this->loadResultSequence[$this->loadResultIndex++] ?? null;
                }

                public function loadObjectList(): array
                {
                    return [];
                }

                public function execute(): bool
                {
                    if (($this->activeQuery->mode ?? '') === 'insert') {
                        $this->insertCalled = true;
                        // VALUES format: "studentId,lessonId,subscribed"
                        $parts = explode(',', (string) $this->activeQuery->insertedValues);
                        $this->insertedSubscribed = (int) ($parts[2] ?? -1);
                    }

                    return true;
                }

                public function insertid(): int
                {
                    return 99;
                }
            };
        }
    }
}
