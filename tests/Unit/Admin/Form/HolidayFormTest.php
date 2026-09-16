<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

declare(strict_types=1);

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Form;

use PHPUnit\Framework\TestCase;

/**
 * Tests for holiday admin form labels and filters.
 *
 * @since  1.3.24
 */
class HolidayFormTest extends TestCase
{
    public function testAdminHolidayFormUsesHolidayDateLabels(): void
    {
        $form = $this->loadFormXml(dirname(__DIR__, 4) . '/components/com_balancirk/admin/forms/holiday.xml');

        $this->assertSame(
            'COM_BALANCIRK_TABLE_TABLEHEAD_START_HOLIDAY',
            $this->fieldLabel($form, 'startDate')
        );
        $this->assertSame(
            'COM_BALANCIRK_TABLE_TABLEHEAD_END_HOLIDAY',
            $this->fieldLabel($form, 'endDate')
        );
        $this->assertSame('false', $this->fieldAttribute($form, 'startDate', 'showtime'));
        $this->assertSame('false', $this->fieldAttribute($form, 'endDate', 'showtime'));
        $this->assertSame('summary', strtolower($this->fieldName($form, 'summary')));
    }

    public function testSharedHolidayFormUsesHolidayDateLabels(): void
    {
        $form = $this->loadFormXml(dirname(__DIR__, 4) . '/components/com_balancirk/forms/holiday.xml');

        $this->assertSame(
            'COM_BALANCIRK_TABLE_TABLEHEAD_START_HOLIDAY',
            $this->fieldLabel($form, 'startDate')
        );
        $this->assertSame(
            'COM_BALANCIRK_TABLE_TABLEHEAD_END_HOLIDAY',
            $this->fieldLabel($form, 'endDate')
        );
        $this->assertSame('summary', strtolower($this->fieldName($form, 'summary')));
    }

    public function testHolidayFilterSortsByStartDateNotLessonStart(): void
    {
        $form = $this->loadFormXml(dirname(__DIR__, 4) . '/components/com_balancirk/admin/forms/filter_holidays.xml');
        $xml = $form->asXML();

        $this->assertIsString($xml);
        $this->assertStringContainsString('a.startDate ASC', $xml);
        $this->assertStringContainsString('a.endDate ASC', $xml);
        $this->assertStringNotContainsString('a.start ASC', $xml);
        $this->assertStringNotContainsString('a.end ASC', $xml);
        $this->assertStringNotContainsString('name="published"', $xml);
        $this->assertStringNotContainsString('JSTATUS_ASC', $xml);
        $this->assertStringNotContainsString('JGLOBAL_TITLE_ASC', $xml);
    }

    private function loadFormXml(string $path): \SimpleXMLElement
    {
        $this->assertFileExists($path);
        $form = simplexml_load_file($path);
        $this->assertNotFalse($form);

        return $form;
    }

    private function fieldLabel(\SimpleXMLElement $form, string $name): string
    {
        $field = $this->field($form, $name);

        return (string) $field['label'];
    }

    private function fieldAttribute(\SimpleXMLElement $form, string $name, string $attribute): string
    {
        $field = $this->field($form, $name);

        return (string) $field[$attribute];
    }

    private function fieldName(\SimpleXMLElement $form, string $name): string
    {
        $field = $this->field($form, $name);

        return (string) $field['name'];
    }

    private function field(\SimpleXMLElement $form, string $name): \SimpleXMLElement
    {
        $matches = $form->xpath('//field[@name="' . $name . '"]');
        $this->assertNotEmpty($matches, 'Missing form field ' . $name);

        return $matches[0];
    }
}
