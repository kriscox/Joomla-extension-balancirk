<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required for loading Admin model classes.
// Bracketed namespace syntax is required when mixing multiple namespace blocks.
//
// NOTE: This file (AdminLessonModelCancellation*) loads before
// AdminLessonModelStudentsTest.php (C < S alphabetically), so it owns the
// AdminModel stub and must include ALL methods that any Admin/Model test needs.
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
             * Default stub: return $data unchanged so validate() callers work
             * without the full Joomla form-validation stack.
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
     * Tests for AdminLessonModel::getCancellationPreview() success paths.
     *
     * Guards and empty-list cases are covered in AdminLessonModelCancellationTest.php
     * (PR #108).  This file covers the full data-flow path that exercises
     * buildRecipientPreview() and the SubscriptionMailHelper integration:
     *
     *  - Enrolled student with one parent member produces a cancellation mail preview.
     *  - Waiting student with one parent member produces a rejection mail preview.
     *  - Enrolled student with no parents yields an entry with an empty recipients list.
     *  - Both enrolled and waiting in the same call are returned in their respective arrays.
     *
     * @since  1.3.26
     */
    class AdminLessonModelCancellationPreviewTest extends TestCase
    {
        /**
         * getCancellationPreview() with one enrolled student who has one parent must
         * return an enrolled entry whose recipient carries a cancellation mail subject
         * and body.
         *
         * @return void
         */
        public function testPreviewBuildsEnrolledEntryWithParentRecipient(): void
        {
            $lesson = $this->makeLesson();
            $enrolled = [$this->makeStudentRow(10, 1, 'Alice', 'Smith')];
            $parentOf10 = [$this->makeMemberRow(20, 'Bob', 'Smith', 'bob@example.com')];

            // Sequence: [enrolled rows, parents of 10, waiting rows]
            $db = $this->makeFakeDb($lesson, [$enrolled, $parentOf10, []]);
            $model = $this->makeModel($db);

            $result = $model->getCancellationPreview(5);

            $this->assertSame($lesson, $result['lesson']);
            $this->assertCount(1, $result['enrolled']);
            $this->assertCount(0, $result['waiting']);

            $entry = $result['enrolled'][0];
            $this->assertSame(1, $entry['subscription_id']);
            $this->assertSame(10, $entry['student_id']);
            $this->assertSame('Alice Smith', $entry['student_name']);
            $this->assertCount(1, $entry['recipients']);

            $recipient = $entry['recipients'][0];
            $this->assertSame(20, $recipient['member_id']);
            $this->assertSame('Bob Smith', $recipient['member_name']);
            $this->assertSame('bob@example.com', $recipient['email']);
            $this->assertIsString($recipient['subject']);
            $this->assertIsString($recipient['body']);
        }

        /**
         * getCancellationPreview() with one waiting student who has one parent must
         * return a waiting entry whose recipient carries a rejection mail preview.
         *
         * @return void
         */
        public function testPreviewBuildsWaitingEntryWithRejectionMail(): void
        {
            $lesson = $this->makeLesson();
            $waiting = [$this->makeStudentRow(11, 2, 'Carol', 'Jones')];
            $parentOf11 = [$this->makeMemberRow(21, 'Dave', 'Jones', 'dave@example.com')];

            // Sequence: [enrolled rows (empty), waiting rows, parents of 11]
            $db = $this->makeFakeDb($lesson, [[], $waiting, $parentOf11]);
            $model = $this->makeModel($db);

            $result = $model->getCancellationPreview(5);

            $this->assertCount(0, $result['enrolled']);
            $this->assertCount(1, $result['waiting']);

            $entry = $result['waiting'][0];
            $this->assertSame(2, $entry['subscription_id']);
            $this->assertSame(11, $entry['student_id']);
            $this->assertSame('Carol Jones', $entry['student_name']);
            $this->assertCount(1, $entry['recipients']);
            $this->assertSame(21, $entry['recipients'][0]['member_id']);
            $this->assertSame('dave@example.com', $entry['recipients'][0]['email']);
            $this->assertIsString($entry['recipients'][0]['subject']);
        }

        /**
         * An enrolled student with no registered parents must still produce an entry in
         * the enrolled list, but with an empty recipients array.
         *
         * @return void
         */
        public function testPreviewEnrolledEntryWithNoParentsHasEmptyRecipients(): void
        {
            $lesson = $this->makeLesson();
            $enrolled = [$this->makeStudentRow(10, 1, 'Eve', 'Brown')];

            // Sequence: [enrolled rows, parents of 10 (none), waiting rows]
            $db = $this->makeFakeDb($lesson, [$enrolled, [], []]);
            $model = $this->makeModel($db);

            $result = $model->getCancellationPreview(5);

            $this->assertCount(1, $result['enrolled']);
            $this->assertSame(10, $result['enrolled'][0]['student_id']);
            $this->assertCount(0, $result['enrolled'][0]['recipients']);
        }

        /**
         * Both enrolled and waiting students must appear in their respective arrays
         * with the correct mail type (cancellation vs rejection) for each.
         *
         * @return void
         */
        public function testPreviewPopulatesBothEnrolledAndWaitingArrays(): void
        {
            $lesson = $this->makeLesson();
            $enrolled = [$this->makeStudentRow(10, 1, 'Alice', 'Smith')];
            $parentOf10 = [$this->makeMemberRow(20, 'Bob', 'Smith', 'bob@example.com')];
            $waiting = [$this->makeStudentRow(11, 2, 'Carol', 'Jones')];
            $parentOf11 = [$this->makeMemberRow(21, 'Dave', 'Jones', 'dave@example.com')];

            // Sequence: enrolled, parents of 10, waiting, parents of 11
            $db = $this->makeFakeDb($lesson, [$enrolled, $parentOf10, $waiting, $parentOf11]);
            $model = $this->makeModel($db);

            $result = $model->getCancellationPreview(5);

            $this->assertCount(1, $result['enrolled']);
            $this->assertCount(1, $result['waiting']);
            $this->assertSame(10, $result['enrolled'][0]['student_id']);
            $this->assertSame(11, $result['waiting'][0]['student_id']);
            // Both entries must have a recipient
            $this->assertCount(1, $result['enrolled'][0]['recipients']);
            $this->assertCount(1, $result['waiting'][0]['recipients']);
        }

        // -----------------------------------------------------------------------
        // Helpers — fixtures
        // -----------------------------------------------------------------------

        /**
         * Build a minimal lesson object used as the loadLessonRow() return value.
         *
         * @return object
         */
        private function makeLesson(): object
        {
            return (object) [
                'id' => 5,
                'name' => 'Acro',
                'year' => 2026,
                'start' => '2026-09-01',
                'end' => '2027-06-30',
            ];
        }

        /**
         * Build a row as returned by LessonCancellationHelper::listEnrolledForCancellation()
         * or listWaitingForCancellation().
         *
         * @param   int     $studentId       Student id.
         * @param   int     $subscriptionId  Subscription id.
         * @param   string  $firstname       Student first name.
         * @param   string  $name            Student last name.
         *
         * @return object
         */
        private function makeStudentRow(
            int $studentId,
            int $subscriptionId,
            string $firstname,
            string $name
        ): object {
            return (object) [
                'subscription_id' => $subscriptionId,
                'student' => $studentId,
                'firstname' => $firstname,
                'name' => $name,
                'birthdate' => '2015-01-01',
            ];
        }

        /**
         * Build a parent member row as returned by LessonCancellationHelper::loadParentMembers().
         *
         * @param   int     $id         Member id.
         * @param   string  $firstname  Member first name.
         * @param   string  $name       Member last name.
         * @param   string  $email      Member email.
         *
         * @return object
         */
        private function makeMemberRow(int $id, string $firstname, string $name, string $email): object
        {
            return (object) [
                'id' => $id,
                'firstname' => $firstname,
                'name' => $name,
                'email' => $email,
            ];
        }

        // -----------------------------------------------------------------------
        // Helpers — model factory
        // -----------------------------------------------------------------------

        /**
         * Create an AdminLessonModel stub backed by a fake database.
         *
         * The stub captures errors for assertion.
         *
         * @param   object  $db  Fake database.
         *
         * @return  AdminLessonModel
         */
        private function makeModel(object $db): AdminLessonModel
        {
            return new class ($db) extends AdminLessonModel {
                /** @var list<string> */
                private array $errors = [];

                public function __construct(private readonly object $db)
                {
                }

                public function getDatabase(): object
                {
                    return $this->db;
                }

                public function setError($error): void
                {
                    $this->errors[] = (string) $error;
                }

                public function getError($i = null, $toString = true): string
                {
                    return $this->errors[0] ?? '';
                }
            };
        }

        // -----------------------------------------------------------------------
        // Helpers — database stub
        // -----------------------------------------------------------------------

        /**
         * Create a fake database that serves a fixed lesson row from loadObject() and
         * drains $listCallResults in order across loadObjectList() calls.
         *
         * Call order for getCancellationPreview() is:
         *   loadObject()      ← loadLessonRow()
         *   loadObjectList()  ← listEnrolledForCancellation()         [index 0]
         *   loadObjectList()  ← loadParentMembers(enrolled student)   [index 1]
         *   ...               (repeated for each additional enrolled student)
         *   loadObjectList()  ← listWaitingForCancellation()          [next index]
         *   loadObjectList()  ← loadParentMembers(waiting student)    [next index]
         *
         * @param   object    $lessonRow         Lesson row returned by loadObject().
         * @param   object[][] $listCallResults   Ordered arrays for each loadObjectList() call.
         *
         * @return  object
         */
        private function makeFakeDb(object $lessonRow, array $listCallResults): object
        {
            $qb = new class {
                public function select(mixed $x): static
                {
                    return $this;
                }

                public function from(mixed $t): static
                {
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

                public function order(mixed $o): static
                {
                    return $this;
                }
            };

            return new class ($qb, $lessonRow, $listCallResults) {
                private int $listIndex = 0;

                public function __construct(
                    private readonly object $qb,
                    private readonly object $lessonRow,
                    private readonly array $listCallResults
                ) {
                }

                public function getQuery(bool $new = false): object
                {
                    return $this->qb;
                }

                public function quoteName(mixed $n, mixed $a = null): mixed
                {
                    if (is_array($n)) {
                        $aliases = is_array($a) ? $a : array_fill(0, count($n), null);

                        return array_map(
                            static fn($name, $alias) => '`' . $name . '`' . ($alias !== null ? ' AS `' . $alias . '`' : ''),
                            $n,
                            $aliases
                        );
                    }

                    return '`' . $n . '`' . ($a !== null ? ' AS `' . $a . '`' : '');
                }

                public function quote(mixed $value): string
                {
                    return "'" . $value . "'";
                }

                public function setQuery(mixed $q): static
                {
                    return $this;
                }

                public function loadObject(): ?object
                {
                    return $this->lessonRow;
                }

                public function loadObjectList(): array
                {
                    return $this->listCallResults[$this->listIndex++] ?? [];
                }
            };
        }
    }
}
