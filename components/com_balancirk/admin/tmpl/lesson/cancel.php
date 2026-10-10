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
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.keepalive');

$preview = $this->cancellationPreview;
$lessonId = (int) ($this->item->id ?? 0);

?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=lesson&layout=cancel&id=' . $lessonId); ?>"
	method="post" name="adminForm" id="lesson-cancel-form">

	<?php if (!$preview) : ?>
		<div class="alert alert-danger"><?= Text::_('JERROR_AN_ERROR_HAS_OCCURRED'); ?></div>
	<?php else : ?>
		<div class="alert alert-warning">
			<?= Text::sprintf('COM_BALANCIRK_LESSON_CANCEL_INTRO', $this->escape($preview['lesson']->name ?? '')); ?>
		</div>

		<h3><?= Text::_('COM_BALANCIRK_LESSON_CANCEL_ENROLLED_MAILS'); ?></h3>
		<?php if (empty($preview['enrolled'])) : ?>
			<div class="alert alert-info"><?= Text::_('COM_BALANCIRK_LESSON_NO_ENROLLED_STUDENTS'); ?></div>
		<?php else : ?>
			<?php foreach ($preview['enrolled'] as $index => $entry) : ?>
				<div class="card mb-3">
					<div class="card-header">
						<strong><?= $this->escape($entry['student_name']); ?></strong>
					</div>
					<div class="card-body">
						<?php foreach ($entry['recipients'] as $rIndex => $recipient) : ?>
							<div class="mb-3 border-bottom pb-3">
								<p class="mb-1">
									<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_EMAIL'); ?>:
									<?= $this->escape($recipient['email']); ?>
									(<?= $this->escape($recipient['member_name']); ?>)
								</p>
								<input type="hidden"
									name="enrolled_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][subscription_id]"
									value="<?= (int) $entry['subscription_id']; ?>" />
								<input type="hidden"
									name="enrolled_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][member_id]"
									value="<?= (int) $recipient['member_id']; ?>" />
								<div class="mb-2">
									<label class="form-label"><?= Text::_('COM_BALANCIRK_LESSON_CANCEL_MAIL_SUBJECT'); ?></label>
									<input type="text" class="form-control"
										name="enrolled_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][subject]"
										value="<?= $this->escape($recipient['subject']); ?>" />
								</div>
								<div>
									<label class="form-label"><?= Text::_('COM_BALANCIRK_LESSON_CANCEL_MAIL_BODY'); ?></label>
									<textarea class="form-control" rows="8"
										name="enrolled_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][body]"><?= $this->escape($recipient['body']); ?></textarea>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<h3 class="mt-4"><?= Text::_('COM_BALANCIRK_LESSON_CANCEL_WAITING_MAILS'); ?></h3>
		<div class="form-check mb-3">
			<input class="form-check-input" type="checkbox" name="dismiss_waiting" id="dismiss_waiting" value="1" checked />
			<label class="form-check-label" for="dismiss_waiting">
				<?= Text::_('COM_BALANCIRK_LESSON_CANCEL_DISMISS_WAITING'); ?>
			</label>
		</div>
		<?php if (empty($preview['waiting'])) : ?>
			<div class="alert alert-info"><?= Text::_('COM_BALANCIRK_LESSON_NO_WAITING_STUDENTS'); ?></div>
		<?php else : ?>
			<?php foreach ($preview['waiting'] as $index => $entry) : ?>
				<div class="card mb-3">
					<div class="card-header">
						<strong><?= $this->escape($entry['student_name']); ?></strong>
						<span class="badge bg-secondary"><?= Text::_('COM_BALANCIRK_LESSON_WAITING_LIST'); ?></span>
					</div>
					<div class="card-body">
						<?php foreach ($entry['recipients'] as $rIndex => $recipient) : ?>
							<div class="mb-3 border-bottom pb-3">
								<p class="mb-1">
									<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_EMAIL'); ?>:
									<?= $this->escape($recipient['email']); ?>
									(<?= $this->escape($recipient['member_name']); ?>)
								</p>
								<input type="hidden"
									name="waiting_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][subscription_id]"
									value="<?= (int) $entry['subscription_id']; ?>" />
								<input type="hidden"
									name="waiting_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][member_id]"
									value="<?= (int) $recipient['member_id']; ?>" />
								<div class="mb-2">
									<label class="form-label"><?= Text::_('COM_BALANCIRK_LESSON_CANCEL_MAIL_SUBJECT'); ?></label>
									<input type="text" class="form-control"
										name="waiting_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][subject]"
										value="<?= $this->escape($recipient['subject']); ?>" />
								</div>
								<div>
									<label class="form-label"><?= Text::_('COM_BALANCIRK_LESSON_CANCEL_MAIL_BODY'); ?></label>
									<textarea class="form-control" rows="8"
										name="waiting_messages[<?= (int) $index; ?>_<?= (int) $rIndex; ?>][body]"><?= $this->escape($recipient['body']); ?></textarea>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<button type="submit" class="btn btn-danger"
			onclick="this.form.task.value='lesson.confirmCancel'; return confirm('<?= htmlspecialchars(Text::_('COM_BALANCIRK_LESSON_CANCEL_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>');">
			<?= Text::_('COM_BALANCIRK_LESSON_CANCEL_SUBMIT'); ?>
		</button>
	<?php endif; ?>

	<input type="hidden" name="id" value="<?= $lessonId; ?>" />
	<input type="hidden" name="task" value="" />
	<?= HTMLHelper::_('form.token'); ?>
</form>
