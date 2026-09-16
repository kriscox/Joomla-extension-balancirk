<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2023 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\UserHelper;
use CoCoCo\Component\Balancirk\Site\Model\LessonModel;
use CoCoCo\Component\Balancirk\Site\Helper\LesdaysHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

HTMLHelper::_('jquery.framework');

/** @var Joomla\CMS\Application $app */
$app = Factory::getApplication();

/** @var LessonModel $lessonModel */
$lessonModel = $this->getModel();
$item = is_object($this->item) ? $this->item : (object) [];
$period = $lessonModel instanceof LessonModel
	? $lessonModel->resolveLessonPeriod($item)
	: LessonModel::periodFromValues($item->start ?? null, $item->end ?? null);
$hasConfiguredPeriod = $period !== null;

if ($hasConfiguredPeriod) {
	$startDate = $period['start'];
	$endDate = $period['end'];
	$firstLesDay = $startDate->format('d/m/Y');
	$lastLesDay = $endDate->format('d/m/Y');
	$firstIso = $startDate->format('Y-m-d');
	$lastIso = $endDate->format('Y-m-d');
} else {
	$startDate = null;
	$endDate = null;
	$firstLesDay = '';
	$lastLesDay = '';
	$firstIso = '';
	$lastIso = '';
}

$lesdaysMask = (int) ($item->lesdays ?? 0);
$userid = Factory::getApplication()->getIdentity()->id;
$joomlaToken = UserHelper::getProfile($userid)->get('joomlatoken');
$api_token = is_array($joomlaToken) ? (string) ($joomlaToken['token'] ?? '') : '';
$presencesUrl = Route::_(
	'index.php?option=com_balancirk&task=lesson.presences&format=json&' . Session::getFormToken() . '=1',
	false
);

$today = (new DateTime())->setTime(0, 0, 0);
$todayInRange = LessonModel::shouldAutoSelectToday($period, $lesdaysMask, $today);

$presenceState = $app->getUserState('com_balancirk.presence.data', []);
$restoredDate = '';
$restoredStudents = [];

if ((int) ($presenceState['id'] ?? 0) === (int) ($item->id ?? 0)) {
	$restoredDate = (string) ($presenceState['date'] ?? '');
	$restoredStudents = isset($presenceState['students']) && is_array($presenceState['students'])
		? $presenceState['students']
		: [];
}

$parsedRestoredDate = $restoredDate !== '' ? LessonModel::parseLessonDate($restoredDate) : null;
$restoredIsValid = $parsedRestoredDate instanceof DateTime
	&& LessonModel::isValidAttendanceDate(
		$parsedRestoredDate,
		$hasConfiguredPeriod ? $startDate : null,
		$hasConfiguredPeriod ? $endDate : null,
		$lesdaysMask
	);
$autoSelectDate = '';
$autoSelectIso = '';

if ($restoredIsValid) {
	$autoSelectDate = $parsedRestoredDate->format('d/m/Y');
	$autoSelectIso = $parsedRestoredDate->format('Y-m-d');
} elseif ($todayInRange) {
	$autoSelectDate = $today->format('d/m/Y');
	$autoSelectIso = $today->format('Y-m-d');
}

/** @var Joomla\CMS\Document\Document  */
$doc = $app->getDocument();
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $doc->getWebAssetManager();
$wa->registerAndUseStyle('lesson', 'media/com_balancirk/css/lesson.css')
	->registerAndUseScript('bootstrap-datepicker', 'https://unpkg.com/bootstrap-datepicker@latest/dist/js/bootstrap-datepicker.min.js')
	->registerAndUseScript('bootstrap-datepicker-nl', 'https://unpkg.com/bootstrap-datepicker@latest/dist/locales/bootstrap-datepicker.nl-BE.min.js')
	->registerAndUseScript('lesson-script', 'media/com_balancirk/js/balancirk_lesson_date.js');
$doc->addScriptOptions('lesson-script', [
	'token' => $api_token,
	'presencesUrl' => $presencesUrl,
	'start' => $firstIso,
	'end' => $lastIso,
	'startDisplay' => $firstLesDay,
	'endDisplay' => $lastLesDay,
	'lesdaysMask' => $lesdaysMask,
	'weekdayBits' => LesdaysHelper::JS_GETDAY_BITS,
	'autoSelectDate' => $autoSelectDate,
	'autoSelectIso' => $autoSelectIso,
	'restoreSelection' => $restoredIsValid && $restoredStudents !== [],
	'strings' => [
		'invalidDate' => Text::_('COM_BALANCIRK_LESSON_PRESENCE_INVALID_DATE'),
	],
]);

$students = $this->get('PresenceStudents') ?: [];
$data = [];
$data['id'] = (int) ($item->id ?? 0);

if ($restoredIsValid && $restoredStudents !== []) {
	$data['students'] = $restoredStudents;
}

$form = $this->get('PresenceForm');
if ($form) {
	$studentsField = $form->getField('students');

	if ($studentsField) {
		foreach ($students as $student) {
			$label = $student->firstname . ' ' . $student->name;
			$option = ['value' => $student->id];

			if ((int) ($student->on_waiting_list ?? 0) === 1) {
				$label .= ' (' . Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_WAITINGLIST') . ')';
				$option['class'] = 'waiting-list-student';
			}

			$studentsField->addOption($label, $option);
		}
	}

	$form->bind($data);
}

$teached_url = Route::_('index.php?option=com_balancirk&view=lesson&layout=teacher&id=' . (int) ($item->id ?? 0));
$url = Route::_('index.php?option=com_balancirk&view=lesson&id=' . (int) ($item->id ?? 0));
?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-edit-top}'); ?>
<form action="<?= $url ?>" method="POST" name="adminForm" id="presence-form" class="form-validate">
	<div class="row">
		<div class="col-md-12">
			<h3><?= Text::_('COM_BALANCIRK_LESSONS_PRESENCES'); ?><?= $this->escape($item->name ?? '') ?></h3>
			<?= $form ? $form->renderField('id') : ''; ?>
			<label for="jform_date"><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_DATES'); ?></label>
			<input type="text" id="jform_date" class="form-control" name="jform[date]" />

			<?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'students')); ?>

			<?= $form ? $form->getInput('students') : ''; ?>
			<input type="hidden" name="task" />
		</div>
		<?= HTMLHelper::_('form.token'); ?>

		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-3">
				<button type="button" class="balancirk_button balancirk_presence_button" onclick="Joomla.submitbutton('lesson.presence')">
					<?= Text::_('JSAVE') ?>
				</button>
			</div>
			<div class="col-12 col-md-3">
				<button type="button" class="balancirk_button balancirk_presence_button" onclick="Joomla.submitbutton('lesson.cancel')">
					<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
				</button>
			</div>
			<div class="col-12 col-md-3">
				<a class="balancirk_presence_button" href="<?= $teached_url ?>">
					<?= Text::_('COM_BALANCIRK_LESSON_TEACHED') ?>
				</a>

			</div>
		</div>
</form>
<div class="modal fade" id="presenceConflictModal" tabindex="-1" aria-labelledby="presenceConflictModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="presenceConflictModalLabel"><?= Text::_('COM_BALANCIRK_LESSON_PRESENCE_CONFLICT_TITLE') ?></h5>
			</div>
			<div class="modal-body">
				<?= Text::_('COM_BALANCIRK_LESSON_PRESENCE_CONFLICT_BODY') ?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" id="presenceKeep"><?= Text::_('COM_BALANCIRK_LESSON_PRESENCE_KEEP') ?></button>
				<button type="button" class="btn btn-outline-primary" id="presenceMerge"><?= Text::_('COM_BALANCIRK_LESSON_PRESENCE_MERGE') ?></button>
				<button type="button" class="btn btn-primary" id="presenceOverwrite"><?= Text::_('COM_BALANCIRK_LESSON_PRESENCE_OVERWRITE') ?></button>
			</div>
		</div>
	</div>
</div>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-edit-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>