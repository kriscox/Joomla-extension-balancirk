<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=student&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="student-form" class="form-validate">

	<?= LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div>
		<?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_BALANCIRK_STUDENT_TAB_DETAILS')); ?>
		<div class="row">
			<div class="col-md-9">
				<div class="row">
					<div class="col-md-6">
						<?= $this->form->renderField('id'); ?>
						<?= $this->form->renderField('firstname'); ?>
						<?= $this->form->renderField('name'); ?>
						<?= $this->form->renderField('email'); ?>
						<?= $this->form->renderField('phone'); ?>
						<?= $this->form->renderField('birthdate'); ?>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card card-light">
					<div class="card-body">
						<?= LayoutHelper::render('joomla.edit.global', $this); ?>
					</div>
				</div>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'adress', Text::_('COM_BALANCIRK_STUDENT_TAB_ADRESS')); ?>
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="col-md-6">
						<?= $this->form->renderField('street'); ?>
						<?= $this->form->renderField('number'); ?>
						<?= $this->form->renderField('bus'); ?>
						<?= $this->form->renderField('postcode'); ?>
						<?= $this->form->renderField('municipality'); ?>
					</div>
				</div>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
</form>