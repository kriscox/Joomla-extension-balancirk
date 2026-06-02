<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="lesson-form" class="form-validate">

	<?= LayoutHelper::render('joomla.edit.title_alias', $this); ?>
	<?= $this->form->renderField('id'); ?>

	<div>
		<?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_BALANCIRK_LESSON_TAB_DETAILS')); ?>
		<div class="row">
			<div class="col-md-9">
				<div class="row">
					<div class="col-md-6">
						<?= $this->form->renderField('type'); ?>
						<?= $this->form->renderField('fee'); ?>
						<?= $this->form->renderField('year'); ?>
						<?= $this->form->renderField('max_students'); ?>
						<?= $this->form->renderField('min_age'); ?>
						<?= $this->form->renderField('max_age'); ?>
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

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'adress', Text::_('COM_BALANCIRK_LESSON_TAB_DATES')); ?>
		<div class="row">
			<div class="col-md-6">
				<div class="row">
					<div class="col-md-6">
						<?= $this->form->renderField('start'); ?>
						<?= $this->form->renderField('end'); ?>
						<?= $this->form->renderField('start_registration'); ?>
						<?= $this->form->renderField('end_registration'); ?>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<?= $this->form->getLabel('lesdays_field'); ?>
				<?= $this->form->getInput('lesdays_field'); ?>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'emails', Text::_('COM_BALANCIRK_LESSON_TAB_EMAILS')); ?>
		<div class="row">
			<div class="col-md-12">
				<?= $this->form->renderField('subscription_email_subject'); ?>
				<?= $this->form->renderField('subscription_email_body'); ?>
				<?= $this->form->renderField('waitinglist_email_subject'); ?>
				<?= $this->form->renderField('waitinglist_email_body'); ?>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'teachers', Text::_('COM_BALANCIRK_LESSON_TAB_TEACHERS')); ?>
		<div class="row">
			<div class="col-md-12">
				<?php
				$assignedIds = array_map(function ($t) {
					return (int) $t->id;
				}, $this->teachers ?? []);
				?>
				<p><strong><?= Text::_('COM_BALANCIRK_LESSON_TEACHERS_DESCRIPTION') ?></strong></p>
				<?php if (!empty($this->availableTeachers)) : ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th style="width:1%"></th>
							<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_FIRSTNAME') ?></th>
							<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_NAME') ?></th>
							<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_EMAIL') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->availableTeachers as $teacher) : ?>
						<tr>
							<td>
								<input type="checkbox" name="jform[teachers][]"
									value="<?= (int) $teacher->id ?>"
									<?= in_array((int) $teacher->id, $assignedIds) ? 'checked' : '' ?> />
							</td>
							<td><?= $this->escape($teacher->firstname) ?></td>
							<td><?= $this->escape($teacher->name) ?></td>
							<td><?= $this->escape($teacher->email) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<div class="alert alert-warning">
					<?= Text::_('COM_BALANCIRK_LESSON_NO_TEACHERS_AVAILABLE') ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
</form>
