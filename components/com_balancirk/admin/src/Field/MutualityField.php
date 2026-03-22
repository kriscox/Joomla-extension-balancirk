<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Mutuality chooser.
 *
 * @since  1.2.12
 */
class MutualityField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.2.12
     */
    protected $type = 'Mutuality';

    /**
     * Returns the field options.
     *
     * @return  array
     *
     * @since   1.2.12
     */
    protected function getOptions()
    {
        $mutualities = $this->getMutualities();
        $options = [
            HTMLHelper::_('select.option', '', '-'),
        ];

        foreach ($mutualities as $mutuality) {
            $options[] = HTMLHelper::_('select.option', $mutuality, $mutuality);
        }

        $currentValue = trim((string) $this->value);

        if ($currentValue !== '' && !in_array($currentValue, $mutualities, true)) {
            $options[] = HTMLHelper::_('select.option', $currentValue, $currentValue);
        }

        return array_merge(parent::getOptions(), $options);
    }

    /**
     * Returns the configured mutuality options.
     *
     * @return  array
     *
     * @since   1.2.12
     */
    private function getMutualities(): array
    {
        $configured = (string) ComponentHelper::getParams('com_balancirk')->get('mutuality_options', '');
        $source = trim($configured) !== '' ? $configured : "CM\nSolidaris\nHelan\nVNZ";
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
