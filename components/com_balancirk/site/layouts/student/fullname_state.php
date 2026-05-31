<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$form  = $displayData->getForm();
$states = array(
    '0' => Text::_('COM_BALANCIRK_STATUS_SUBSCRIBED'),
    '1' => Text::_('COM_BALANCIRK_STATUS_UNSUBSCRIBED'),
    'published' => Text::_('COM_BALANCIRK_STATUS_UNSUBSCRIBED'),
    '2' => Text::_('JARCHIVED'),
    'archived' => Text::_('JARCHIVED'),
    '-2' => Text::_('JTRASHED')
);

$fullname = $form->getField('firstname')->value . " " . $form->getField('name')->value;
$state = (string) $form->getField('state')->value;
$stateLabel = $states[$state] ?? $states['1'];

?>
<div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
        <h1> <?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') ?> </h1>
    </div>
    <div class="col-12 col-md-6">
        <h5><?= Text::_('COM_BALANCIRK_STATUS_LABEL') . ":  " . $stateLabel; ?> </h5>
    </div>
</div>