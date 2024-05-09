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
    '2' => Text::_('JARCHIVED'),
    '-2' => Text::_('JTRASHED')
);

$fullname = $form->getField('firstname')->value . " " . $form->getField('name')->value;

?>
<div class="row title-alias form-vertical mb-3">
	<div class="col-12 col-md-6">
		<h1> <?= $fullname ?> </h1>
	</div>
	<div class="col-12 col-md-6">
		<h5><?= TEXT::_('COM_BALANCIRK_STATUS_LABEL') . ":  " . $states[$form->getField('state')->value]; ?> </h5>
	</div>
</div>