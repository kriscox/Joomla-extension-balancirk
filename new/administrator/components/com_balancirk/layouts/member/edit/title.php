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

$title = $form->getField('title') ? 'title' : ($form->getField('username') ? 'username' : '');

?>
<div class="row title-alias form-vertical mb-3">
	<div class="col-12 col-md-6">
		<?php echo $title ? $form->renderField($title) : ''; ?>
	</div>
</div>