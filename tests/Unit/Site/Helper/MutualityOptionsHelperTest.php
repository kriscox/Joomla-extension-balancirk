<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Helper;

use PHPUnit\Framework\TestCase;
use CoCoCo\Component\Balancirk\Site\Helper\MutualityOptionsHelper;

/**
 * Test class for mutuality option parsing.
 *
 * @since  1.2.12
 */
class MutualityOptionsHelperTest extends TestCase
{
    public function testFallsBackToDefaultMutualities(): void
    {
        $this->assertSame(
            ['CM', 'Solidaris', 'Helan', 'VNZ'],
            MutualityOptionsHelper::getOptions('')
        );
    }

    public function testParsesConfiguredMutualitiesAndRemovesDuplicates(): void
    {
        $configured = " Partena \nCM\nPartena\nHelan;Helan,Securex ";

        $this->assertSame(
            ['Partena', 'CM', 'Helan', 'Securex'],
            MutualityOptionsHelper::getOptions($configured)
        );
    }

    public function testNullInputFallsBackToDefaultMutualities(): void
    {
        $this->assertSame(
            ['CM', 'Solidaris', 'Helan', 'VNZ'],
            MutualityOptionsHelper::getOptions(null)
        );
    }

    public function testWhitespaceOnlyInputFallsBackToDefaultMutualities(): void
    {
        $this->assertSame(
            ['CM', 'Solidaris', 'Helan', 'VNZ'],
            MutualityOptionsHelper::getOptions('   ')
        );
    }

    public function testSemicolonSeparatedInputIsParsedCorrectly(): void
    {
        $this->assertSame(
            ['CM', 'VNZ'],
            MutualityOptionsHelper::getOptions('CM;VNZ')
        );
    }

    public function testCommaSeparatedInputIsParsedCorrectly(): void
    {
        $this->assertSame(
            ['CM', 'VNZ'],
            MutualityOptionsHelper::getOptions('CM,VNZ')
        );
    }
}
