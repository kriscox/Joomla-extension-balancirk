<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace Joomla\CMS\MVC\Model {
    if (!class_exists(AdminModel::class)) {
        class AdminModel
        {
        }
    }
}

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model {
    use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Test class for lesson date helpers.
     *
     * @since  1.3.1
     */
    class LessonModelTest extends TestCase
    {
        public function testGetDatesIncludesLessonEndDateWhenItIsALessonDay(): void
        {
            $dates = LessonModel::getDates(
                '2026-05-01',
                '2026-05-08',
                [
                    'Monday' => 0,
                    'Tuesday' => 0,
                    'Wednesday' => 0,
                    'Thursday' => 0,
                    'Friday' => 1,
                    'Saturday' => 0,
                    'Sunday' => 0,
                ]
            );

            $this->assertSame(
                ['2026-05-01', '2026-05-08'],
                array_map(static fn(\DateTimeInterface $date): string => $date->format('Y-m-d'), $dates)
            );
        }
    }
}
