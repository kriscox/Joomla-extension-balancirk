<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Helper;

\defined('_JEXEC') or die;

/**
 * Helper for accounting subscription exports.
 *
 * @since  1.2.22
 */
class AccountingExportHelper
{
    /**
     * Export column order.
     *
     * @var    array<int, string>
     * @since  1.2.22
     */
    private const COLUMNS = [
        'firstname',
        'name',
        'address',
        'bus',
        'postcode',
        'city',
        'email',
        'lesson',
        'student_firstname',
        'student_name',
        'uitpas',
        'mutuality',
    ];

    /**
     * Normalize a row to the export column order.
     *
     * @param   array<string, mixed>  $row  Export row data.
     *
     * @return  array<string, string>
     *
     * @since   1.2.22
     */
    public static function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach (self::COLUMNS as $column) {
            $value = $row[$column] ?? '';
            $normalized[$column] = is_scalar($value) ? trim((string) $value) : '';
        }

        return $normalized;
    }

    /**
     * Render the export in CSV format.
     *
     * @param   array<int, array<string, string>>  $rows  Export rows.
     *
     * @return  string
     *
     * @since   1.2.22
     */
    public static function renderCsv(array $rows): string
    {
        $lines = [self::renderCsvLine(self::COLUMNS)];

        foreach ($rows as $row) {
            $lines[] = self::renderCsvLine(array_values(self::normalizeRow($row)));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Render the export as an Excel-compatible HTML table.
     *
     * @param   array<int, array<string, string>>  $rows  Export rows.
     *
     * @return  string
     *
     * @since   1.2.22
     */
    public static function renderXls(array $rows): string
    {
        $cells = '<tr>';

        foreach (self::COLUMNS as $column) {
            $cells .= '<th>' . htmlspecialchars($column, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $cells .= '</tr>';

        foreach ($rows as $row) {
            $cells .= '<tr>';

            foreach (self::normalizeRow($row) as $value) {
                $cells .= '<td>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
            }

            $cells .= '</tr>';
        }

        return '<table>' . $cells . '</table>';
    }

    /**
     * Render a single CSV line with consistent quoting.
     *
     * @param   array<int, string>  $values  CSV values.
     *
     * @return  string
     *
     * @since   1.2.22
     */
    private static function renderCsvLine(array $values): string
    {
        return implode(
            ';',
            array_map(
                static function (string $value): string {
                    return '"' . str_replace('"', '""', $value) . '"';
                },
                $values
            )
        );
    }
}
