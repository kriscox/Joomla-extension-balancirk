<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<?php echo HtmlHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HtmlHelper::_('content.prepare', '{loadposition balancirk-subscription-top}'); ?>
<?php if (empty($this->students)) : ?>
	<div class="alert alert-info">
		<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
		<?= Text::_('COM_BALANCIRK_NO_STUDENTS'); ?>
	</div>
<?php elseif (empty($this->lessons)) : ?>
	<div class="alert alert-info">
		<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
		<?= Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SUBSCRIPTION'); ?>
	</div>
<?php else : ?>
	<form action="<?= Route::_('index.php?option=com_balancirk&view=subscription'); ?>" method="post" id="subscription-form" name="adminForm" class="form-validate">
		<div class="col col-md-6">
			<fieldset>
				<?= $this->form->renderField('student'); ?>
			</fieldset>
		</div>
		<div class="col col-md-6">
			<fieldset addfieldpath="com_balancirk/src/Field/">
				<?= $this->form->renderField('lesson'); ?>
			</fieldset>
			<script type="application/json" id="lessons-by-student"><?= json_encode($this->lessonsByStudent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
		</div>
		<input type="hidden" class="hidden" name="task" value="">
		<?= HTMLHelper::_('form.token'); ?>
		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('subscription.add')">
					<span class="icon-save"> <?= Text::_('JSAVE') ?> </span>
				</button>
			</div>
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('subscription.cancel')">
					<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
				</button>
			</div>
		</div>

	</form>
<?php endif; ?>
<?php echo HtmlHelper::_('content.prepare', '{loadposition balancirk-subscription-bottom}'); ?>
<?php echo HtmlHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
<script>
(function () {
	var studentField = document.getElementById('jform_student');
	var lessonField = document.getElementById('jform_lesson');
	var lessonsByStudentEl = document.getElementById('lessons-by-student');

	if (!studentField || !lessonField || !lessonsByStudentEl) {
		return;
	}

	var lessonsByStudent = JSON.parse(lessonsByStudentEl.textContent || '{}');
	var defaultOption = lessonField.options.length > 0 ? lessonField.options[0].cloneNode(true) : null;

	function updateLessons() {
		var studentId = studentField.value;
		var lessons = lessonsByStudent[studentId] || {};

		lessonField.innerHTML = '';
		if (defaultOption) {
			lessonField.appendChild(defaultOption.cloneNode(true));
		}

		Object.keys(lessons).forEach(function (lessonId) {
			var option = document.createElement('option');
			option.value = lessonId;
			option.textContent = lessons[lessonId];
			lessonField.appendChild(option);
		});

		lessonField.disabled = Object.keys(lessons).length === 0;
	}

	studentField.addEventListener('change', updateLessons);
	updateLessons();
})();
</script>
