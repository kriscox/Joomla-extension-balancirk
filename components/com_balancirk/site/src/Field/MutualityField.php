<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Field;

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
        $options = [
            HTMLHelper::_('select.option', '', '-'),
        ];

        foreach ($this->getMutualities() as $mutuality) {
            $options[] = HTMLHelper::_('select.option', $mutuality, $mutuality);
        }

        $currentValue = trim((string) $this->value);

        if ($currentValue !== '' && !in_array($currentValue, $this->getMutualities(), true)) {
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
        $defaults = ['CM', 'Solidaris', 'Helan', 'VNZ'];
        $configured = (string) ComponentHelper::getParams('com_balancirk')->get('mutuality_options', '');
        $entries = preg_split('/[\r\n,;]+/', $configured) ?: [];
        $mutualities = [];

        foreach (array_merge($defaults, $entries) as $entry) {
            $entry = trim((string) $entry);

            if ($entry === '' || in_array($entry, $mutualities, true)) {
                continue;
            }

            $mutualities[] = $entry;
        }

        return $mutualities;
    }
}
