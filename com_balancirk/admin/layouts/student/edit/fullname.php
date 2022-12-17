<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

$form  = $displayData->getForm();

$fullname = $form->getField('firstname')->value . " " . $form->getField('name')->value;

?>
<div class="row title-alias form-vertical mb-3">
	<div class="col-12 col-md-6">
		<h1> <?= $fullname ?> </h1>
	</div>
</div>