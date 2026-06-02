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
use CoCoCo\Component\Balancirk\Site\Helper\AccountingExportHelper;

/**
 * Test class for accounting subscription exports.
 *
 * @since  1.2.22
 */
class AccountingExportHelperTest extends TestCase
{
    public function testNormalizeRowReturnsExpectedColumnsInOrder(): void
    {
        $row = AccountingExportHelper::normalizeRow([
            'lesson' => 'Acro',
            'firstname' => 'Anna',
            'student_name' => 'Peeters',
            'mutuality' => 'CM',
        ]);

        $this->assertSame(
            [
                'firstname' => 'Anna',
                'name' => '',
                'address' => '',
                'bus' => '',
                'postcode' => '',
                'city' => '',
                'email' => '',
                'lesson' => 'Acro',
                'student_firstname' => '',
                'student_name' => 'Peeters',
                'uitpas' => '',
                'mutuality' => 'CM',
            ],
            $row
        );
    }

    public function testRenderCsvUsesSemicolonSeparatedQuotedHeaders(): void
    {
        $csv = AccountingExportHelper::renderCsv([
            [
                'firstname' => 'Anna',
                'name' => 'Janssens',
                'address' => 'Stationsstraat 1',
                'bus' => '2',
                'postcode' => '9000',
                'city' => 'Gent',
                'email' => 'anna@example.com',
                'lesson' => 'Acro',
                'student_firstname' => 'Mila',
                'student_name' => 'Janssens',
                'uitpas' => '12345',
                'mutuality' => 'CM',
            ],
        ]);

        $lines = preg_split("/\r\n|\n|\r/", trim($csv));

        $this->assertSame('"firstname";"name";"address";"bus";"postcode";"city";"email";"lesson";"student_firstname";"student_name";"uitpas";"mutuality"', $lines[0]);
        $this->assertSame('"Anna";"Janssens";"Stationsstraat 1";"2";"9000";"Gent";"anna@example.com";"Acro";"Mila";"Janssens";"12345";"CM"', $lines[1]);
    }

    public function testRenderXlsEscapesValues(): void
    {
        $xls = AccountingExportHelper::renderXls([
            [
                'firstname' => '<Anna>',
                'name' => 'Janssens',
                'address' => '',
                'bus' => '',
                'postcode' => '',
                'city' => '',
                'email' => '',
                'lesson' => 'Acro',
                'student_firstname' => '',
                'student_name' => '',
                'uitpas' => '',
                'mutuality' => '',
            ],
        ]);

        $this->assertStringContainsString('<table>', $xls);
        $this->assertStringContainsString('&lt;Anna&gt;', $xls);
        $this->assertStringContainsString('<th>student_firstname</th>', $xls);
    }

    public function testRenderCsvEscapesDoubleQuotesInsideValues(): void
    {
        $csv = AccountingExportHelper::renderCsv([
            [
                'firstname' => 'Ann "A"',
                'name' => 'Janssens',
                'address' => '',
                'bus' => '',
                'postcode' => '',
                'city' => '',
                'email' => '',
                'lesson' => '',
                'student_firstname' => '',
                'student_name' => '',
                'uitpas' => '',
                'mutuality' => '',
            ],
        ]);

        $this->assertStringContainsString('"Ann ""A"""', $csv);
    }

    public function testRenderCsvWithNoRowsReturnsOnlyHeaderLine(): void
    {
        $csv = AccountingExportHelper::renderCsv([]);
        $lines = preg_split("/\r\n|\n|\r/", trim($csv));

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('"firstname"', $lines[0]);
        $this->assertStringContainsString('"mutuality"', $lines[0]);
    }

    public function testNormalizeRowTrimsLeadingAndTrailingWhitespace(): void
    {
        $row = AccountingExportHelper::normalizeRow([
            'firstname' => '  Anna  ',
            'city' => ' Gent ',
        ]);

        $this->assertSame('Anna', $row['firstname']);
        $this->assertSame('Gent', $row['city']);
    }

    public function testNormalizeRowConvertsNonScalarValueToEmptyString(): void
    {
        $row = AccountingExportHelper::normalizeRow([
            'firstname' => ['nested', 'array'],
            'name' => null,
        ]);

        $this->assertSame('', $row['firstname']);
        $this->assertSame('', $row['name']);
    }

    public function testRenderXlsWithNoRowsReturnsTableWithHeaderOnly(): void
    {
        $xls = AccountingExportHelper::renderXls([]);

        $this->assertStringContainsString('<table>', $xls);
        $this->assertStringContainsString('<th>firstname</th>', $xls);
        $this->assertStringNotContainsString('<td>', $xls);
    }
}
