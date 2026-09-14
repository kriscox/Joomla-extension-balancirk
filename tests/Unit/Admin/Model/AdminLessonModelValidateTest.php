<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required for loading the Admin LessonModel class.
// Bracketed namespace syntax is required when mixing multiple namespace blocks.
//
// IMPORTANT: This file must load before LessonModelTeachersTest.php and
// MemberModelSaveDispatchTest.php (alphabetically 'A' < 'L' < 'M').  It adds
// validate() to the AdminModel stub so that AdminLessonModel::validate() can
// call parent::validate() during tests.  The other test files in this directory
// will skip redefining AdminModel because class_exists() returns true by then.
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
             * Default stub: return $data as-is so validate() callers can work
             * without the full Joomla form-validation stack.
             */
            public function validate($form, $data, $group = null)
            {
                return $data;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\LessonModel as AdminLessonModel;
    use PHPUnit\Framework\Attributes\PreserveGlobalState;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for AdminLessonModel::validate() age-range guard.
     *
     * The validate() override adds one business rule on top of the parent form
     * validation: if both min_age and max_age are present the max must be ≥ min.
     * A wrong answer lets admins save a lesson whose age range can never match
     * any student, silently blocking registrations.
     *
     * @since  1.2.12
     */
    class AdminLessonModelValidateTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // Pass-through cases — no error should be raised
        // -----------------------------------------------------------------------

        /**
         * When both min_age and max_age are null the guard must be skipped and
         * the data array returned unchanged.
         *
         * @return void
         */
        public function testValidatePassesThroughWhenBothAgesAreNull(): void
        {
            $data   = ['min_age' => null, 'max_age' => null, 'name' => 'Acrobatiek'];
            $result = $this->makeModel()->validate(null, $data);

            $this->assertSame($data, $result);
        }

        /**
         * When min_age is an empty string the guard condition short-circuits
         * and the data must be returned unchanged.
         *
         * @return void
         */
        public function testValidatePassesThroughWhenMinAgeIsEmptyString(): void
        {
            $data   = ['min_age' => '', 'max_age' => '5'];
            $result = $this->makeModel()->validate(null, $data);

            $this->assertSame($data, $result);
        }

        /**
         * When max_age is an empty string the guard condition short-circuits
         * and the data must be returned unchanged.
         *
         * @return void
         */
        public function testValidatePassesThroughWhenMaxAgeIsEmptyString(): void
        {
            $data   = ['min_age' => '5', 'max_age' => ''];
            $result = $this->makeModel()->validate(null, $data);

            $this->assertSame($data, $result);
        }

        /**
         * When max_age > min_age the configuration is valid and the data must
         * be returned unchanged.
         *
         * @return void
         */
        public function testValidatePassesThroughWhenMaxAgeExceedsMinAge(): void
        {
            $data   = ['min_age' => '5', 'max_age' => '12'];
            $result = $this->makeModel()->validate(null, $data);

            $this->assertSame($data, $result);
        }

        /**
         * When max_age === min_age the ages are equal, which is the boundary
         * condition.  Equal ages should be accepted (only strict less-than fails).
         *
         * @return void
         */
        public function testValidatePassesThroughWhenMaxAgeEqualsMinAge(): void
        {
            $data   = ['min_age' => '10', 'max_age' => '10'];
            $result = $this->makeModel()->validate(null, $data);

            $this->assertSame($data, $result);
        }

        // -----------------------------------------------------------------------
        // Rejection case — max_age < min_age must return false
        // -----------------------------------------------------------------------

        /**
         * When max_age < min_age the method must record an error and return false.
         *
         * This test runs in an isolated subprocess so that the Joomla\CMS\Language\Text
         * stub can be defined safely without affecting other tests in the suite
         * (notably SubscriptionMailHelperTest, which relies on Text NOT being defined).
         *
         * @return void
         */
        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testValidateReturnsFalseWhenMaxAgeLessThanMinAge(): void
        {
            // Define a minimal Text stub in this isolated process only.
            if (!class_exists(\Joomla\CMS\Language\Text::class)) {
                // phpcs:ignore Squiz.PHP.Eval.Discouraged
                eval('namespace Joomla\\CMS\\Language; class Text { public static function _(string $k): string { return $k; } }');
            }

            $model  = $this->makeModel();
            $result = $model->validate(null, ['min_age' => '10', 'max_age' => '5']);

            $this->assertFalse($result, 'max_age < min_age must be rejected');
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        /**
         * Create a concrete AdminLessonModel instance with a minimal constructor.
         *
         * @return AdminLessonModel
         */
        private function makeModel(): AdminLessonModel
        {
            return new class extends AdminLessonModel {
                public function __construct()
                {
                }
            };
        }
    }
}
