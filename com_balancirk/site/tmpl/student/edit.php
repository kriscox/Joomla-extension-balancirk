<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=student&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="student-form" class="form-validate">

	<?= LayoutHelper::render('student.fullname_state', $this); ?>

	<div>
		<div class="row">
			<div class="col-md-6">
				<?= $this->form->renderField('firstname'); ?>
			</div>
			<div class="col-md-6">
				<?= $this->form->renderField('name'); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<?= $this->form->renderField('street'); ?>
			</div>
			<div class="col-md-3">
				<?= $this->form->renderField('number'); ?>
			</div>
			<div class="col-md-3">
				<?= $this->form->renderField('bus'); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<?= $this->form->renderField('postcode'); ?>
			</div>
			<div class="col-md-6">
				<?= $this->form->renderField('city'); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<?= $this->form->renderField('email'); ?>
			</div>
			<div class="col-md-6">
				<?= $this->form->renderField('phone'); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<?= $this->form->renderField('birthdate'); ?>
			</div>
			<div class="col-md-6">
				<?= $this->form->renderField('uitpas'); ?>
			</div>
		</div>
	</div>
	<input type="hidden" name="jform[id]" id="jform_id" value="<?= $this->item->id ?>">
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
	<div class="row title-alias form-vertical mb-3">
		<div class="col-12 col-md-6">
			<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('student.save')">
				<span class="icon-save"> <?= Text::_('JSAVE') ?> </span>
			</button>
		</div>
		<div class="col-12 col-md-6">
			<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('student.cancel')">
				<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
			</button>
		</div>
	</div>
</form>