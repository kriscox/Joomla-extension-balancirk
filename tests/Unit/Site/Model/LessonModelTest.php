<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
use PHPUnit\Framework\TestCase;

if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    class_alias(\stdClass::class, \Joomla\CMS\MVC\Model\AdminModel::class);
}

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
