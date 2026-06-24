<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Site\Model;

use PHPUnit\Framework\TestCase;

/**
 * Tests for member additional-field handling.
 *
 * @since  1.3.16
 */
class MemberModelTest extends TestCase
{
    /**
     * Member additional columns should tolerate missing optional keys.
     *
     * @return void
     */
    public function testAdditionalMemberValuesUseDefaultsForMissingKeys(): void
    {
        $data = [
            'firstname' => 'Ada',
        ];

        $values = $this->buildAdditionalMemberValues(42, $data);

        $this->assertSame(
            [42, 'Ada', '', '', '', '', '', ''],
            $values
        );
    }

    /**
     * Mirror the value extraction used by MemberModel::saveToTable().
     *
     * @param   int    $id    Member id.
     * @param   array  $data  Form payload.
     *
     * @return  array
     */
    private function buildAdditionalMemberValues(int $id, array $data): array
    {
        return [
            $id,
            $data['firstname'] ?? '',
            $data['street'] ?? '',
            $data['number'] ?? '',
            $data['bus'] ?? '',
            $data['postcode'] ?? '',
            $data['city'] ?? '',
            $data['phone'] ?? '',
        ];
    }
}
