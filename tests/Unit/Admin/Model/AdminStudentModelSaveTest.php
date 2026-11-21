<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// No new Joomla stubs needed here: AdminLessonModelStudentsTest.php (which
// runs first alphabetically, A-L < A-S) already defined AdminModel with the
// save($data) stub that captures $capturedSaveData.
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\StudentModel as AdminStudentModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for admin StudentModel::save() — increased_benefit normalisation.
     *
     * When an HTML checkbox is unchecked the browser omits the key entirely from
     * the POST body.  save() must coerce any absent, empty, or falsy value to the
     * integer 0 before forwarding to parent::save(), and any truthy value to 1.
     *
     * Regression risk: without this guard a silent NULL or empty-string could be
     * stored in a boolean column, causing the accounting export and site display
     * to show incorrect values.
     *
     * @since  1.3.27
     */
    class AdminStudentModelSaveTest extends TestCase
    {
        protected function setUp(): void
        {
            \Joomla\CMS\MVC\Model\AdminModel::$capturedSaveData = null;
        }

        // -----------------------------------------------------------------------
        // increased_benefit normalisation — absent / falsy values → 0
        // -----------------------------------------------------------------------

        /**
         * When increased_benefit is absent (unchecked checkbox) save() must
         * normalise it to 0 before forwarding to parent::save().
         *
         * @return void
         */
        public function testSaveNormalisesAbsentIncreasedBenefitToZero(): void
        {
            $model = $this->makeModel();
            $model->save(['student' => 5, 'lesson' => 10]);

            $this->assertSame(
                0,
                \Joomla\CMS\MVC\Model\AdminModel::$capturedSaveData['increased_benefit'] ?? 'NOT_SET',
                'Missing increased_benefit key must be normalised to 0'
            );
        }

        /**
         * When increased_benefit is an empty string save() must normalise it to 0.
         *
         * @return void
         */
        public function testSaveNormalisesEmptyStringIncreasedBenefitToZero(): void
        {
            $model = $this->makeModel();
            $model->save(['increased_benefit' => '']);

            $this->assertSame(
                0,
                \Joomla\CMS\MVC\Model\AdminModel::$capturedSaveData['increased_benefit'],
                'Empty-string increased_benefit must be normalised to 0'
            );
        }

        /**
         * When increased_benefit is null save() must normalise it to 0.
         *
         * @return void
         */
        public function testSaveNormalisesNullIncreasedBenefitToZero(): void
        {
            $model = $this->makeModel();
            $model->save(['increased_benefit' => null]);

            $this->assertSame(
                0,
                \Joomla\CMS\MVC\Model\AdminModel::$capturedSaveData['increased_benefit'],
                'Null increased_benefit must be normalised to 0'
            );
        }

        // -----------------------------------------------------------------------
        // increased_benefit normalisation — truthy values → 1
        // -----------------------------------------------------------------------

        /**
         * When increased_benefit is the string '1' save() must normalise it to
         * the integer 1.
         *
         * @return void
         */
        public function testSaveNormalisesStringOneIncreasedBenefitToOne(): void
        {
            $model = $this->makeModel();
            $model->save(['increased_benefit' => '1']);

            $this->assertSame(
                1,
                \Joomla\CMS\MVC\Model\AdminModel::$capturedSaveData['increased_benefit'],
                'String "1" increased_benefit must be normalised to integer 1'
            );
        }

        /**
         * When increased_benefit is the integer 1 save() must keep it as 1.
         *
         * @return void
         */
        public function testSaveNormalisesIntegerOneIncreasedBenefitToOne(): void
        {
            $model = $this->makeModel();
            $model->save(['increased_benefit' => 1]);

            $this->assertSame(
                1,
                \Joomla\CMS\MVC\Model\AdminModel::$capturedSaveData['increased_benefit'],
                'Integer 1 increased_benefit must remain 1'
            );
        }

        // -----------------------------------------------------------------------
        // Helper
        // -----------------------------------------------------------------------

        /**
         * Create a minimal AdminStudentModel stub without a real Joomla constructor.
         *
         * @return AdminStudentModel
         */
        private function makeModel(): AdminStudentModel
        {
            return new class extends AdminStudentModel {
                public function __construct()
                {
                    // Bypass the Joomla AdminModel constructor.
                }
            };
        }
    }
}
