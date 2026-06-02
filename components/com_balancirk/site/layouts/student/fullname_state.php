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

$view = $displayData['view'];
$form  = $view->getForm();
$fullname = $form->getField('firstname')->value . " " . $form->getField('name')->value;
$hasCurrentYearSubscription = (bool) ($displayData['hasCurrentYearSubscription'] ?? false);
$statusLabel = $hasCurrentYearSubscription
    ? Text::_('COM_BALANCIRK_STATUS_SUBSCRIBED')
    : Text::_('COM_BALANCIRK_STATUS_UNSUBSCRIBED');

?>
<div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
        <h1> <?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') ?> </h1>
    </div>
    <div class="col-12 col-md-6">
        <h5><?= Text::_('COM_BALANCIRK_STATUS_LABEL') . ":  " . $statusLabel; ?> </h5>
    </div>
</div>