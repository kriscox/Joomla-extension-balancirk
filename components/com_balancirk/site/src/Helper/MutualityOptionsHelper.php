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
 * Helper methods for configured mutuality options.
 *
 * @since  1.2.12
 */
class MutualityOptionsHelper
{
    /**
     * Parse mutualities from component configuration.
     *
     * @param   string|null  $configured  Raw configured value.
     *
     * @return  array
     *
     * @since   1.2.12
     */
    public static function getOptions(?string $configured): array
    {
        $source = trim((string) $configured) !== '' ? (string) $configured : "CM\nSolidaris\nHelan\nVNZ";
        $entries = preg_split('/[\r\n,;]+/', $source) ?: [];
        $mutualities = [];

        foreach ($entries as $entry) {
            $entry = trim((string) $entry);

            if ($entry === '' || in_array($entry, $mutualities, true)) {
                continue;
            }

            $mutualities[] = $entry;
        }

        return $mutualities;
    }
}
