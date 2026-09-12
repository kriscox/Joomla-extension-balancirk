<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

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

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\LessonModel as AdminLessonModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for AdminLessonModel::save() teacher sync dispatch.
     *
     * @since  1.3.18
     */
    class LessonModelSaveDispatchTest extends TestCase
    {
        /**
         * save() without teachers key must not sync teacher assignments.
         *
         * @return void
         */
        public function testSaveWithoutTeachersKeyDoesNotSyncTeachers(): void
        {
            $saveTeachersCalled = false;

            $model = new class ($saveTeachersCalled) extends AdminLessonModel {
                public function __construct(private bool &$saveTeachersCalled)
                {
                }

                protected function saveLessonRecord(array $data): bool
                {
                    return true;
                }

                public function saveTeachers(int $lessonId, array $teacherIds): bool
                {
                    $this->saveTeachersCalled = true;

                    return true;
                }
            };

            $this->assertTrue($model->save(['id' => 5, 'end_registration' => '2026-09-30']));
            $this->assertFalse($saveTeachersCalled);
        }

        /**
         * save() with teachers key must sync teacher assignments.
         *
         * @return void
         */
        public function testSaveWithTeachersKeySyncsTeachers(): void
        {
            $capturedLessonId = null;
            $capturedTeacherIds = null;

            $model = new class ($capturedLessonId, $capturedTeacherIds) extends AdminLessonModel {
                public function __construct(
                    private mixed &$capturedLessonId,
                    private mixed &$capturedTeacherIds
                ) {
                }

                public function getState($property = null, $default = null): mixed
                {
                    if ($property === 'lesson.id') {
                        return 5;
                    }

                    return $default;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [];
                }

                protected function saveLessonRecord(array $data): bool
                {
                    return true;
                }

                public function saveTeachers(int $lessonId, array $teacherIds): bool
                {
                    $this->capturedLessonId = $lessonId;
                    $this->capturedTeacherIds = $teacherIds;

                    return true;
                }
            };

            $this->assertTrue($model->save(['id' => 5, 'teachers' => [3, 1, 3]]));
            $this->assertSame(5, $capturedLessonId);
            $this->assertSame([1, 3], $capturedTeacherIds);
        }

        /**
         * save() must fail before lesson save when teacher removal is blocked.
         *
         * @return void
         */
        public function testSaveFailsBeforeLessonSaveWhenTeacherRemovalBlocked(): void
        {
            $lessonRecordSaved = false;

            $model = new class ($lessonRecordSaved) extends AdminLessonModel {
                public function __construct(private bool &$lessonRecordSaved)
                {
                }

                public function getState($property = null, $default = null): mixed
                {
                    if ($property === 'lesson.id') {
                        return 5;
                    }

                    return $default;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [2];
                }

                public function countTeachedRecords(int $memberId, ?int $lessonId = null): int
                {
                    return ($memberId === 2 && $lessonId === 5) ? 1 : 0;
                }

                protected function saveLessonRecord(array $data): bool
                {
                    $this->lessonRecordSaved = true;

                    return true;
                }
            };

            $this->assertFalse($model->save(['id' => 5, 'teachers' => []]));
            $this->assertFalse($lessonRecordSaved);
        }

        /**
         * save() must return false when saveTeachers fails after lesson save.
         *
         * @return void
         */
        public function testSaveReturnsFalseWhenSaveTeachersFails(): void
        {
            $model = new class extends AdminLessonModel {
                public function getState($property = null, $default = null): mixed
                {
                    if ($property === 'lesson.id') {
                        return 5;
                    }

                    return $default;
                }

                public function getTeacherIdsForLesson(int $lessonId): array
                {
                    return [];
                }

                protected function saveLessonRecord(array $data): bool
                {
                    return true;
                }

                public function saveTeachers(int $lessonId, array $teacherIds): bool
                {
                    return false;
                }
            };

            $this->assertFalse($model->save(['id' => 5, 'teachers' => [1]]));
        }
    }
}
