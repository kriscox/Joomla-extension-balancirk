<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$document = Factory::getApplication()->getDocument();
$document->getWebAssetManager()->registerAndUseScript(
	'balancirk-lesson-waitlist',
	'media/com_balancirk/js/balancirk_lesson_waitlist.js',
	['version' => 'auto']
);
$document->getWebAssetManager()->registerAndUseScript(
	'balancirk-lesson-settle',
	'media/com_balancirk/js/balancirk_lesson_settle.js',
	['version' => 'auto']
);
$document->addScriptOptions('balancirk-lesson-waitlist', [
	'originalMaxStudents' => (int) ($this->item->max_students ?? 0),
	'enrolledCount' => count($this->subscribedStudents ?? []),
	'waitingCount' => count($this->waitingListStudents ?? []),
	'confirmTemplate' => Text::_('COM_BALANCIRK_LESSON_PROMOTE_WAITLIST_CONFIRM'),
	'yesLabel' => Text::_('JYES'),
	'noLabel' => Text::_('JNO'),
]);
$fifoIds = [];
foreach ($this->waitlistOrdered ?? [] as $row) {
	$fifoIds[] = (int) $row->id;
}
$document->addScriptOptions('balancirk-lesson-settle', [
	'fifoIds' => $fifoIds,
]);

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
						<?= $this->form->renderField('registration_closed'); ?>
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
				<?= $this->form->renderField('promotion_email_subject'); ?>
				<?= $this->form->renderField('promotion_email_body'); ?>
				<?= $this->form->renderField('yearstart_email_subject'); ?>
				<?= $this->form->renderField('yearstart_email_body'); ?>
				<?= $this->form->renderField('rejection_email_subject'); ?>
				<?= $this->form->renderField('rejection_email_body'); ?>
				<?= $this->form->renderField('cancellation_email_subject'); ?>
				<?= $this->form->renderField('cancellation_email_body'); ?>
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
				<?php // Marker so save() always syncs teachers from this admin form (even when all boxes are unchecked). ?>
				<input type="hidden" name="jform[teachers_sync]" value="1" />
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


		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'students', Text::_('COM_BALANCIRK_LESSON_TAB_STUDENTS')); ?>
		<div class="row">
			<div class="col-md-12">
				<?php if ((int) ($this->item->id ?? 0) <= 0) : ?>
					<div class="alert alert-info">
						<?= Text::_('COM_BALANCIRK_LESSON_STUDENTS_SAVE_FIRST'); ?>
					</div>
				<?php else : ?>
					<?php
					$return = base64_encode('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . (int) $this->item->id);
					$token = Session::getFormToken();
					?>
					<?php if ($this->canCreateSubscription) : ?>
						<p>
							<a class="btn btn-success"
								href="<?= Route::_('index.php?option=com_balancirk&view=subscription&layout=edit&lesson=' . (int) $this->item->id . '&return=' . $return); ?>">
								<?= Text::_('COM_BALANCIRK_SUBSCRIPTION_TOOLBAR_ENROL'); ?>
							</a>
						</p>
					<?php endif; ?>

					<h3><?= Text::_('COM_BALANCIRK_LESSON_ENROLLED_STUDENTS'); ?></h3>
					<?php if (empty($this->subscribedStudents)) : ?>
						<div class="alert alert-info"><?= Text::_('COM_BALANCIRK_LESSON_NO_ENROLLED_STUDENTS'); ?></div>
					<?php else : ?>
						<table class="table table-striped">
							<thead>
								<tr>
									<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_FIRSTNAME'); ?></th>
									<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_NAME'); ?></th>
									<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_BIRTHDATE'); ?></th>
									<?php if ($this->canDeleteSubscription) : ?>
										<th class="w-10 text-center"><?= Text::_('COM_BALANCIRK_SUBSCRIPTIONS_HEADING_ACTIONS'); ?></th>
									<?php endif; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($this->subscribedStudents as $student) : ?>
									<tr>
										<td><?= $this->escape($student->firstname); ?></td>
										<td><?= $this->escape($student->name); ?></td>
										<td><?= $this->escape($student->birthdate); ?></td>
										<?php if ($this->canDeleteSubscription) : ?>
											<td class="text-center">
												<a class="btn btn-sm btn-danger"
													href="<?= Route::_('index.php?option=com_balancirk&task=subscription.delete&id=' . (int) $student->subscription_id . '&return=' . $return . '&' . $token . '=1'); ?>"
													onclick="return confirm('<?= htmlspecialchars(Text::_('COM_BALANCIRK_SUBSCRIPTION_CONFIRM_DELETE'), ENT_QUOTES, 'UTF-8'); ?>');">
													<?= Text::_('COM_BALANCIRK_SUBSCRIPTION_REMOVE'); ?>
												</a>
											</td>
										<?php endif; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<h3 class="mt-4"><?= Text::_('COM_BALANCIRK_LESSON_WAITING_LIST'); ?></h3>
					<?php if (empty($this->waitlistOrdered)) : ?>
						<div class="alert alert-info"><?= Text::_('COM_BALANCIRK_LESSON_NO_WAITING_STUDENTS'); ?></div>
					<?php else : ?>
						<p class="text-muted"><?= Text::_('COM_BALANCIRK_WAITLIST_SETTLE_FIFO_HINT'); ?></p>
						<table class="table table-striped" id="balancirk-waitlist-settle-table">
							<thead>
								<tr>
									<th class="w-5"><?= Text::_('COM_BALANCIRK_WAITLIST_FIFO_RANK'); ?></th>
									<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_FIRSTNAME'); ?></th>
									<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_NAME'); ?></th>
									<th><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_BIRTHDATE'); ?></th>
									<th class="text-center"><?= Text::_('COM_BALANCIRK_WAITLIST_ACTION_PROMOTE'); ?></th>
									<th class="text-center"><?= Text::_('COM_BALANCIRK_WAITLIST_ACTION_DISMISS'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($this->waitlistOrdered as $student) : ?>
									<tr data-subscription-id="<?= (int) $student->id; ?>" data-fifo-rank="<?= (int) $student->fifo_rank; ?>">
										<td><span class="badge bg-secondary"><?= (int) $student->fifo_rank; ?></span></td>
										<td><?= $this->escape($student->firstname); ?></td>
										<td><?= $this->escape($student->name); ?></td>
										<td><?= $this->escape($student->birthdate); ?></td>
										<td class="text-center">
											<input type="checkbox" class="balancirk-promote-check"
												form="waitlist-settle-form"
												name="promote_ids[]" value="<?= (int) $student->id; ?>" />
										</td>
										<td class="text-center">
											<input type="checkbox" class="balancirk-dismiss-check"
												form="waitlist-settle-form"
												name="dismiss_ids[]" value="<?= (int) $student->id; ?>" />
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<div class="form-check mb-3">
							<input type="hidden" form="waitlist-settle-form" name="close_registration" value="0" />
							<input class="form-check-input" type="checkbox" form="waitlist-settle-form"
								name="close_registration" id="close_registration" value="1" checked />
							<label class="form-check-label" for="close_registration">
								<?= Text::_('COM_BALANCIRK_WAITLIST_SETTLE_CLOSE_REGISTRATION'); ?>
							</label>
						</div>
						<button type="submit" class="btn btn-primary" form="waitlist-settle-form" id="balancirk-settle-waitlist-btn">
							<?= Text::_('COM_BALANCIRK_WAITLIST_SETTLE_SUBMIT'); ?>
						</button>
					<?php endif; ?>

					<div class="mt-4">
						<?php if ((int) ($this->item->registration_closed ?? 0) === 1) : ?>
							<span class="badge bg-warning text-dark"><?= Text::_('COM_BALANCIRK_LESSON_BADGE_FULL'); ?></span>
							<a class="btn btn-sm btn-outline-secondary"
								href="<?= Route::_('index.php?option=com_balancirk&task=lesson.reopenRegistration&id=' . (int) $this->item->id . '&' . $token . '=1'); ?>">
								<?= Text::_('COM_BALANCIRK_LESSON_REOPEN_REGISTRATION'); ?>
							</a>
						<?php else : ?>
							<a class="btn btn-sm btn-outline-warning"
								href="<?= Route::_('index.php?option=com_balancirk&task=lesson.closeRegistration&id=' . (int) $this->item->id . '&' . $token . '=1'); ?>">
								<?= Text::_('COM_BALANCIRK_LESSON_CLOSE_REGISTRATION'); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="jform[promote_waitlist]" id="jform_promote_waitlist" value="0" />
	<div class="modal fade" id="balancirk-promote-waitlist-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<p id="balancirk-promote-waitlist-message" class="mb-0"></p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" id="balancirk-promote-waitlist-no" data-bs-dismiss="modal">
						<?= Text::_('JNO'); ?>
					</button>
					<button type="button" class="btn btn-outline-secondary" id="balancirk-promote-waitlist-yes" data-bs-dismiss="modal">
						<?= Text::_('JYES'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="balancirk-fifo-override-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<p class="mb-0"><?= Text::_('COM_BALANCIRK_WAITLIST_SETTLE_FIFO_CONFIRM'); ?></p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Text::_('JCANCEL'); ?></button>
					<button type="button" class="btn btn-danger" id="balancirk-fifo-override-confirm"><?= Text::_('JYES'); ?></button>
				</div>
			</div>
		</div>
	</div>
	<?= HTMLHelper::_('form.token'); ?>
</form>
<form action="<?= Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
	method="post" name="waitlistSettleForm" id="waitlist-settle-form" class="d-none">
	<input type="hidden" name="confirm_fifo_override" id="confirm_fifo_override" value="0" />
	<input type="hidden" name="id" value="<?= (int) ($this->item->id ?? 0); ?>" />
	<input type="hidden" name="task" value="lesson.settleWaitlist" />
	<?= HTMLHelper::_('form.token'); ?>
</form>
