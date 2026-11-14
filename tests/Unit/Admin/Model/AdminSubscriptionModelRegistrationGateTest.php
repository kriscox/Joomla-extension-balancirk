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
// All tests in this file use #[RunInSeparateProcess] so the stubs are
// re-defined for each test via defineStubs().
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
     * Tests for the registration-gate check in AdminSubscriptionModel::add().
     *
     * When the caller does NOT have admin create rights (canCreate() = false),
     * add() must only proceed when the lesson is currently open for registration.
     * Admins (canCreate() = true) may always enrol regardless of the gate.
     *
     * @since  1.3.26
     */
    class AdminSubscriptionModelRegistrationGateTest extends TestCase
    {
        /**
         * A non-admin user must be blocked when the lesson has registration closed.
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddRejectsNonAdminWhenRegistrationClosed(): void
        {
            $this->defineStubs();

            $lesson = $this->makeClosedLesson();
            $db = $this->makeDatabase($lesson);
            $model = $this->makeModel($db, canCreate: false);

            $result = $model->add(['student' => 3, 'lesson' => 5]);

            $this->assertFalse($result);
            $this->assertStringContainsString(
                'COM_BALANCIRK_SUBSCRIPTION_REGISTRATION_CLOSED',
                $model->getError(),
                'Non-admin must receive REGISTRATION_CLOSED when lesson is closed'
            );
        }

        /**
         * An admin user must be able to enrol even when the lesson is closed for
         * public registration (canCreate() bypasses the gate).
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddAdminBypassesClosedRegistrationGate(): void
        {
            $this->defineStubs();

            $lesson = $this->makeClosedLesson();
            // loadResultSequence: [null, 5] → subscriptionExists()=false, countLessonSubscriptions()=5
            // 5 < 20 → directly enrolled
            $db = $this->makeDatabase($lesson, loadResultSequence: [null, 5]);
            $model = $this->makeModel($db, canCreate: true);

            $result = $model->add(['student' => 3, 'lesson' => 5, 'override_age' => 1]);

            $this->assertTrue($result);
            $this->assertTrue($db->insertCalled, 'Admin must bypass the gate and INSERT the enrolment');
        }

        /**
         * A non-admin user must be allowed to enrol when the lesson registration
         * window is currently open.
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddNonAdminProceedsWhenRegistrationOpen(): void
        {
            $this->defineStubs();

            $lesson = $this->makeOpenLesson();
            // loadResultSequence: [null, 5] → subscriptionExists()=false, countLessonSubscriptions()=5
            $db = $this->makeDatabase($lesson, loadResultSequence: [null, 5]);
            $model = $this->makeModel($db, canCreate: false);

            // override_age=1 skips the age-eligibility DB round-trip.
            $result = $model->add(['student' => 3, 'lesson' => 5, 'override_age' => 1]);

            $this->assertTrue($result);
            $this->assertTrue($db->insertCalled, 'Non-admin must be able to enrol during an open registration window');
        }

        // -----------------------------------------------------------------------
        // Helpers — stubs
        // -----------------------------------------------------------------------

        /**
         * Define Text and ComponentHelper stubs for an isolated process.
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
        // Helpers — lesson fixtures
        // -----------------------------------------------------------------------

        /**
         * Build a future lesson with registration_closed = 1 (gate closed).
         *
         * @return object
         */
        private function makeClosedLesson(): object
        {
            return (object) [
                'id' => 5,
                'year' => 2099,
                'state' => '1',
                'start' => '2099-09-01',
                'end' => '2100-06-30',
                'start_registration' => '2099-01-01',
                'end_registration' => '2099-12-31',
                'registration_closed' => 1,
                'min_age' => null,
                'max_age' => null,
                'max_students' => 20,
                'name' => 'Closed lesson',
            ];
        }

        /**
         * Build a future lesson whose registration window spans today (open).
         *
         * @return object
         */
        private function makeOpenLesson(): object
        {
            return (object) [
                'id' => 5,
                'year' => 2099,
                'state' => '1',
                'start' => '2099-09-01',
                'end' => '2100-06-30',
                'start_registration' => '2000-01-01',
                'end_registration' => '2099-12-31',
                'registration_closed' => 0,
                'min_age' => null,
                'max_age' => null,
                'max_students' => 20,
                'name' => 'Open lesson',
            ];
        }

        // -----------------------------------------------------------------------
        // Helpers — model factory
        // -----------------------------------------------------------------------

        /**
         * Create an AdminSubscriptionModel stub with a fake database and
         * configurable canCreate() return value.
         *
         * @param   object  $db         Fake database.
         * @param   bool    $canCreate  Return value for canCreate().
         *
         * @return  AdminSubscriptionModel
         */
        private function makeModel(object $db, bool $canCreate): AdminSubscriptionModel
        {
            return new class ($db, $canCreate) extends AdminSubscriptionModel {
                /** @var list<string> */
                private array $errors = [];

                public function __construct(
                    private readonly object $db,
                    private readonly bool $canCreateValue
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

                public function canCreate(): bool
                {
                    return $this->canCreateValue;
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
         * Build a fake database that routes by FROM table name.
         *
         * loadObject(): returns the given lesson when FROM contains 'lessons',
         *               null for any other table (student not found → mail skipped).
         * loadResult(): drains $loadResultSequence in order.
         * execute():    sets insertCalled = true and captures the subscribed value.
         *
         * @param   object       $lesson              Lesson row.
         * @param   list<mixed>  $loadResultSequence  Return values for loadResult() calls.
         *
         * @return  object
         */
        private function makeDatabase(
            object $lesson,
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

            return new class ($qb, $lesson, $loadResultSequence) {
                public bool $insertCalled = false;
                public int $insertedSubscribed = -1;
                private object $activeQuery;
                private int $loadResultIndex = 0;

                public function __construct(
                    private readonly object $qb,
                    private readonly object $lesson,
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
