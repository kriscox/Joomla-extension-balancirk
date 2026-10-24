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
//
// IMPORTANT ORDERING NOTE: AdminLessonModelCancellationTest (C) loads before
// AdminLessonModelStudentsTest (S) and AdminLessonModelValidateTest (V) in the
// suite run order.  This file therefore MUST define AdminModel with validate()
// and getState() so that S and V, which rely on the already-registered stub,
// continue to work correctly.
// ---------------------------------------------------------------------------

namespace Joomla\CMS\MVC\Model {
    if (!class_exists('Joomla\\CMS\\MVC\\Model\\AdminModel')) {
        abstract class AdminModel
        {
            // No typed property declarations — subclasses freely redeclare them.

            public function setError(string $error): void
            {
            }

            public function getError(): string
            {
                return '';
            }

            /**
             * Default stub: return $data unchanged so AdminLessonModel::validate()
             * can call parent::validate() without the full Joomla form-validation stack.
             */
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

namespace Joomla\CMS\Component {
    if (!class_exists('Joomla\\CMS\\Component\\ComponentHelper')) {
        class ComponentHelper
        {
            public static function getParams(string $extension): object
            {
                return new class {
                    public function get(string $key, string $default = ''): string
                    {
                        return $default;
                    }
                };
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
     * Tests for AdminLessonModel::getCancellationPreview() and cancelLessonWithMails().
     *
     * Both methods were introduced in 1.3.25 / PR #103 and previously had no
     * unit-test coverage.  Key regression risks:
     *
     * getCancellationPreview():
     *  - lessonId ≤ 0 must be rejected before any DB call.
     *  - A missing lesson row in the DB must cause a hard failure.
     *  - With enrolled/waiting rows returns a correctly structured array
     *    including the lesson object, and per-row recipients built by
     *    buildRecipientPreview().
     *
     * cancelLessonWithMails():
     *  - lessonId ≤ 0 must be rejected before any DB call.
     *  - A missing lesson row must cause a hard failure.
     *  - With empty student lists the method executes cancelLesson(),
     *    dismisses enrolled/waiting (no-ops), and returns the summary array.
     *
     * @since  1.3.25
     */
    class AdminLessonModelCancellationTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // getCancellationPreview() — guard: invalid lessonId
        // -----------------------------------------------------------------------

        /**
         * lessonId = 0 must be rejected before any database call.
         *
         * loadLessonRow() returns null for lessonId ≤ 0 without hitting the DB,
         * so getCancellationPreview() must return false without calling getDatabase().
         *
         * @return void
         */
        public function testGetCancellationPreviewReturnsFalseForZeroLessonId(): void
        {
            $model = $this->makeModelWithoutDb();

            $this->assertFalse($model->getCancellationPreview(0));
        }

        /**
         * Negative lessonId must also be rejected.
         *
         * @return void
         */
        public function testGetCancellationPreviewReturnsFalseForNegativeLessonId(): void
        {
            $model = $this->makeModelWithoutDb();

            $this->assertFalse($model->getCancellationPreview(-5));
        }

        // -----------------------------------------------------------------------
        // getCancellationPreview() — guard: lesson not found in DB
        // -----------------------------------------------------------------------

        /**
         * When the DB returns no row for the given lessonId, getCancellationPreview()
         * must return false (lesson not found).
         *
         * @return void
         */
        public function testGetCancellationPreviewReturnsFalseWhenLessonRowNotFound(): void
        {
            $db = $this->makeFakeDb(null);
            $model = $this->makeModelWithDb($db);

            $this->assertFalse($model->getCancellationPreview(5));
        }

        // -----------------------------------------------------------------------
        // getCancellationPreview() — success: empty enrolled and waiting lists
        // -----------------------------------------------------------------------

        /**
         * When the lesson exists and both enrolled and waiting lists are empty,
         * getCancellationPreview() must return an array with the lesson object
         * and two empty recipient lists.
         *
         * @return void
         */
        public function testGetCancellationPreviewReturnsStructuredArrayWithEmptyLists(): void
        {
            $lesson = (object) ['id' => 5, 'name' => 'Acro'];
            $db = $this->makeFakeDb($lesson, [[], []]);
            $model = $this->makeModelWithDb($db);

            $result = $model->getCancellationPreview(5);

            $this->assertIsArray($result);
            $this->assertSame($lesson, $result['lesson']);
            $this->assertSame([], $result['enrolled']);
            $this->assertSame([], $result['waiting']);
        }

        // -----------------------------------------------------------------------
        // getCancellationPreview() — success: one enrolled row, no parents
        // -----------------------------------------------------------------------

        /**
         * With one enrolled student (who has no parent records) the enrolled list
         * must contain one entry with the correct structure and an empty recipients
         * array.  This exercises the buildRecipientPreview() private method.
         *
         * @return void
         */
        public function testGetCancellationPreviewReturnsEnrolledRowWithEmptyRecipients(): void
        {
            $lesson = (object) ['id' => 7, 'name' => 'Trampoline'];
            $enrolledRow = (object) [
                'subscription_id' => 10,
                'student' => 3,
                'firstname' => 'Lena',
                'name' => 'Peeters',
                'birthdate' => '2015-06-01',
            ];

            // DB call sequence:
            //   loadObject()      → lesson row
            //   loadObjectList()  1 → enrolled rows (one row from listEnrolledForCancellation)
            //   loadObjectList()  2 → parent members for student 3 (empty, from loadParentMembers)
            //   loadObjectList()  3 → waiting rows (empty, from listWaitingForCancellation)
            $db = $this->makeFakeDb($lesson, [[$enrolledRow], [], []]);
            $model = $this->makeModelWithDb($db);

            $result = $model->getCancellationPreview(7);

            $this->assertIsArray($result);
            $this->assertCount(1, $result['enrolled']);
            $this->assertSame([], $result['waiting']);

            $entry = $result['enrolled'][0];
            $this->assertSame(10, $entry['subscription_id']);
            $this->assertSame(3, $entry['student_id']);
            $this->assertSame('Lena Peeters', $entry['student_name']);
            $this->assertSame([], $entry['recipients']);
        }

        // -----------------------------------------------------------------------
        // cancelLessonWithMails() — guard: invalid lessonId
        // -----------------------------------------------------------------------

        /**
         * lessonId = 0 must be rejected without any database access.
         *
         * @return void
         */
        public function testCancelLessonWithMailsReturnsFalseForZeroLessonId(): void
        {
            $model = $this->makeModelWithoutDb();

            $this->assertFalse($model->cancelLessonWithMails(0, []));
        }

        /**
         * A negative lessonId must also be rejected.
         *
         * @return void
         */
        public function testCancelLessonWithMailsReturnsFalseForNegativeLessonId(): void
        {
            $model = $this->makeModelWithoutDb();

            $this->assertFalse($model->cancelLessonWithMails(-3, []));
        }

        // -----------------------------------------------------------------------
        // cancelLessonWithMails() — guard: lesson not found in DB
        // -----------------------------------------------------------------------

        /**
         * When the DB returns no row for the lessonId, cancelLessonWithMails()
         * must return false.
         *
         * @return void
         */
        public function testCancelLessonWithMailsReturnsFalseWhenLessonRowNotFound(): void
        {
            $db = $this->makeFakeDb(null);
            $model = $this->makeModelWithDb($db);

            $this->assertFalse($model->cancelLessonWithMails(5, []));
        }

        // -----------------------------------------------------------------------
        // cancelLessonWithMails() — success: no enrolled or waiting students
        // -----------------------------------------------------------------------

        /**
         * With an empty enrolled list and an empty waiting list, cancelLessonWithMails()
         * must cancel the lesson in the DB and return the summary array with all
         * counts at zero.
         *
         * The method calls LessonCancellationHelper::cancelLesson() (which executes
         * an UPDATE query) before iterating enrolled/waiting rows, so the DB fake
         * must support execute().
         *
         * @return void
         */
        public function testCancelLessonWithMailsReturnsResultArrayWithEmptyStudentLists(): void
        {
            $lesson = (object) ['id' => 9, 'name' => 'Aerial'];

            // DB call sequence:
            //   loadObject()      → lesson row
            //   setQuery+execute  → cancelLesson UPDATE (tracked via $cancelExecuted)
            //   loadObjectList()  1 → listEnrolledForCancellation → []
            //   loadObjectList()  2 → listWaitingForCancellation  → []
            $cancelExecuted = false;
            $db = $this->makeFakeDb($lesson, [[], []], $cancelExecuted);
            $model = $this->makeModelWithDb($db);

            $result = $model->cancelLessonWithMails(9, []);

            $this->assertIsArray($result);
            $this->assertTrue($result['cancelled']);
            $this->assertSame(0, $result['mailed']);
            $this->assertSame(0, $result['unenrolled']);
            $this->assertSame(0, $result['dismissed']);
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Create an AdminLessonModel stub that throws if getDatabase() is called.
         *
         * @return AdminLessonModel
         */
        private function makeModelWithoutDb(): AdminLessonModel
        {
            return new class extends AdminLessonModel {
                public function __construct()
                {
                }

                public function getState($property = null, $default = null): mixed
                {
                    return $default;
                }

                public function getDatabase(): never
                {
                    throw new \LogicException('getDatabase() must not be called for this input');
                }
            };
        }

        /**
         * Create an AdminLessonModel stub with an injected fake database.
         *
         * @param   object  $db  Fake database.
         *
         * @return  AdminLessonModel
         */
        private function makeModelWithDb(object $db): AdminLessonModel
        {
            return new class ($db) extends AdminLessonModel {
                public function __construct(private readonly object $db)
                {
                }

                public function getState($property = null, $default = null): mixed
                {
                    return $default;
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }
            };
        }

        /**
         * Build a fake database for cancellation tests.
         *
         * loadObject() returns $lessonRow (or null when the lesson is not found).
         * loadObjectList() returns each entry from $listCallResults in order,
         * falling back to [] for any additional call.
         *
         * The returned object also supports execute() so that cancelLesson()'s
         * UPDATE query can complete without error.
         *
         * @param   object|null  $lessonRow        Row returned by loadObject().
         * @param   array        $listCallResults  Sequence of arrays for loadObjectList().
         * @param   bool         &$cancelExecuted  Optional reference, set to true on execute().
         *
         * @return  object
         */
        private function makeFakeDb(
            ?object $lessonRow,
            array $listCallResults = [],
            bool &$cancelExecuted = false
        ): object {
            return new class ($lessonRow, $listCallResults, $cancelExecuted) {
                private int $listCallIndex = 0;

                public function __construct(
                    private readonly ?object $lessonRow,
                    private readonly array $listCallResults,
                    private bool &$cancelExecuted
                ) {
                }

                public function getQuery(bool $new = false): object
                {
                    return new class {
                        public function select(mixed $x): static
                        {
                            return $this;
                        }

                        public function from(mixed $x): static
                        {
                            return $this;
                        }

                        public function join(mixed $type, mixed $cond): static
                        {
                            return $this;
                        }

                        public function where(mixed $x): static
                        {
                            return $this;
                        }

                        public function order(mixed $x): static
                        {
                            return $this;
                        }

                        public function update(mixed $x): static
                        {
                            return $this;
                        }

                        public function set(mixed $x): static
                        {
                            return $this;
                        }

                        public function delete(mixed $x): static
                        {
                            return $this;
                        }
                    };
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        return array_map(static fn($x) => '`' . $x . '`', $n);
                    }

                    $s = '`' . $n . '`';

                    return $a !== null ? $s . ' AS `' . $a . '`' : $s;
                }

                public function quote(mixed $v): string
                {
                    return "'" . $v . "'";
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function execute(): void
                {
                    $this->cancelExecuted = true;
                }

                public function loadObject(): ?object
                {
                    return $this->lessonRow;
                }

                public function loadObjectList(): array
                {
                    if ($this->listCallIndex < count($this->listCallResults)) {
                        return $this->listCallResults[$this->listCallIndex++];
                    }

                    return [];
                }
            };
        }
    }
}
