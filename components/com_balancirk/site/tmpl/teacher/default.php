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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Get data
$teachers = $this->teachers;
$lessons = $this->lessons;

// Input fields for filter
$input = Factory::getApplication()->input;
$teacherId = $input->getInt('teacher_id');
$startDate = $input->get('start_date', '', 'string');
$endDate = $input->get('end_date', '', 'string');

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<?= HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?= HtmlHelper::_('content.prepare', '{loadposition balancirk-subscriptions-top}'); ?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=teacher'); ?>" method="post" id="teacher-form" name="adminForm" class="form-validate">
	<label for="teacher_id"><?= Text::_('COM_BALANCIRK_TEACHER_SELECT') ?> :</label>
	<select name="teacher_id" id="teacher_id">
		<option value="">-- <?= Text::_('COM_BALANCIRK_TEACHER_NONE') ?> --</option>
		<option value="Kris Cox" <?= $teacherId == '156' ? 'selected' : ''; ?>>Kris Cox</option>
		<?php if (!empty($teachers)) : ?>
			<?php foreach ($teachers as $teacher) : ?>
				<option value="<?= $teacher->id; ?>" <?= $teacher->id == $teacherId ? 'selected' : ''; ?>>
					<?= htmlspecialchars($teacher->firstname . ' ' . $teacher->name); ?>
				</option>
			<?php endforeach; ?>
		<?php endif; ?>
	</select>

	<label for="start_date">Start Date:</label>
	<input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($startDate); ?>">

	<label for="end_date">End Date:</label>
	<input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($endDate); ?>">

	<button type="submit">Filter</button>
</form>

<?php if (!empty($lessons)): ?>
	<h2>Lessons Taught</h2>
	<table>
		<thead>
			<tr>
				<th>Lesson ID</th>
				<th>Lesson Name</th>
				<th>Date</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($lessons as $lesson): ?>
				<tr>
					<td><?= htmlspecialchars($lesson->id); ?></td>
					<td><?= htmlspecialchars($lesson->lesson); ?></td>
					<td><?= htmlspecialchars($lesson->date); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<?= HTMLHelper::_('content.prepare', '{loadposition balancirk-subscriptions-bottom}'); ?>
<?= HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>