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

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\SubscriptionModel as AdminSubscriptionModel;
    use PHPUnit\Framework\Attributes\PreserveGlobalState;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for admin SubscriptionModel::add() age override behaviour.
     *
     * @since  1.3.x
     */
    class AdminSubscriptionModelAgeOverrideTest extends TestCase
    {
        /**
         * Age mismatch without override must fail and keep the enrolment blocked.
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddRejectsAgeMismatchWithoutOverride(): void
        {
            $this->defineStubs();

            $db = $this->makeDatabase($this->makeLesson(), $this->makeIneligibleStudent());
            $model = $this->makeModel($db);

            $result = $model->add([
                'student' => 7,
                'lesson' => 5,
                'override_age' => 0,
            ]);

            $this->assertFalse($result);
            $this->assertStringContainsString('COM_BALANCIRK_SUBSCRIPTION_AGE_MISMATCH', $model->getError());
            $this->assertStringContainsString('COM_BALANCIRK_SUBSCRIPTION_AGE_OVERRIDE_HINT', $model->getError());
            $this->assertFalse($db->insertCalled);
        }

        /**
         * Missing override_age must behave like No (default).
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddRejectsAgeMismatchWhenOverrideMissing(): void
        {
            $this->defineStubs();

            $db = $this->makeDatabase($this->makeLesson(), $this->makeIneligibleStudent());
            $model = $this->makeModel($db);

            $result = $model->add([
                'student' => 7,
                'lesson' => 5,
            ]);

            $this->assertFalse($result);
            $this->assertFalse($db->insertCalled);
        }

        /**
         * Age mismatch with override_age=1 must enrol the student.
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testAddAllowsAgeMismatchWhenOverrideYes(): void
        {
            $this->defineStubs();

            $db = $this->makeDatabase($this->makeLesson(), $this->makeIneligibleStudent());
            $model = $this->makeModel($db);

            $result = $model->add([
                'student' => 7,
                'lesson' => 5,
                'override_age' => 1,
            ]);

            $this->assertTrue($result);
            $this->assertTrue($db->insertCalled);
            $this->assertSame(42, $model->getLastInsertedId());
        }

        /**
         * Define Text and ComponentHelper stubs for an isolated process.
         *
         * @return void
         */
        private function defineStubs(): void
        {
            if (!class_exists(\Joomla\CMS\Language\Text::class)) {
                // phpcs:ignore Squiz.PHP.Eval.Discouraged
                eval('namespace Joomla\\CMS\\Language; class Text { public static function _(string $k): string { return $k; } public static function sprintf(string $k, mixed ...$a): string { return $k; } }');
            }

            if (!class_exists(\Joomla\CMS\Component\ComponentHelper::class)) {
                // phpcs:ignore Squiz.PHP.Eval.Discouraged
                eval('namespace Joomla\\CMS\\Component; class ComponentHelper { public static function getParams(string $e): object { return new class { public function get(string $k, mixed $d = null): mixed { return $d; } }; } }');
            }
        }

        /**
         * Build a future lesson whose age band excludes the test student.
         *
         * @return object
         */
        private function makeLesson(): object
        {
            return (object) [
                'id' => 5,
                'year' => 2099,
                'state' => '1',
                'start' => '2099-09-01',
                'end' => '2100-06-30',
                'start_registration' => '2099-01-01',
                'end_registration' => '2099-12-31',
                'min_age' => 8,
                'max_age' => 10,
                'max_students' => 20,
                'name' => 'Test lesson',
            ];
        }

        /**
         * Build a student who is far older than the lesson age band.
         *
         * @return object
         */
        private function makeIneligibleStudent(): object
        {
            return (object) [
                'id' => 7,
                'birthdate' => '2000-01-01',
                'firstname' => 'Test',
                'name' => 'Student',
            ];
        }

        /**
         * Create a SubscriptionModel with a fake database and staff create rights.
         *
         * @param   object  $db  Fake database.
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

        /**
         * Build a database stub that answers lesson/student/subscription queries.
         *
         * @param   object  $lesson   Lesson row.
         * @param   object  $student  Student row.
         *
         * @return  object
         */
        private function makeDatabase(object $lesson, object $student): object
        {
            $qb = new class {
                public string $from = '';
                public string $mode = 'select';

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

            return new class ($qb, $lesson, $student) {
                public bool $insertCalled = false;
                private object $activeQuery;

                public function __construct(
                    private readonly object $qb,
                    private readonly object $lesson,
                    private readonly object $student
                ) {
                    $this->activeQuery = $qb;
                }

                public function getQuery(bool $new = true): object
                {
                    if ($new) {
                        $this->qb->from = '';
                        $this->qb->mode = 'select';
                    }

                    $this->activeQuery = $this->qb;

                    return $this->qb;
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        return array_map(static fn ($part) => '`' . $part . '`', $n);
                    }

                    $name = '`' . $n . '`';

                    if ($a !== null) {
                        return $name . ' AS `' . $a . '`';
                    }

                    return $name;
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
                    return null;
                }

                public function loadObjectList(): array
                {
                    return [];
                }

                public function execute(): bool
                {
                    if (($this->activeQuery->mode ?? '') === 'insert') {
                        $this->insertCalled = true;
                    }

                    return true;
                }

                public function insertid(): int
                {
                    return 42;
                }
            };
        }
    }
}
