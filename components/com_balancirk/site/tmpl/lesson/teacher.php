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
use Joomla\CMS\User\UserHelper;
use CoCoCo\Component\Balancirk\Site\Model\LessonModel;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

HTMLHelper::_('jquery.framework');

/** @var Joomla\CMS\Application $app */
$app = Factory::getApplication();

/** @var LessonModel $lessonModel */
$lessonModel = $this->getModel();
$period = $lessonModel instanceof LessonModel
	? $lessonModel->resolveLessonPeriod($this->item)
	: LessonModel::periodFromValues($this->item->start ?? null, $this->item->end ?? null);

if ($period === null) {
	echo '<div class="alert alert-info">' . Text::_('COM_BALANCIRK_LESSON_NO_PERIOD') . '</div>';
	return;
}

$lesdayMask = LessonModel::getLesdays((int) ($this->item->lesdays ?? 0));
$restrictToLesdays = LessonModel::hasConfiguredLesdays($lesdayMask);
$lesdays = $restrictToLesdays
	? LessonModel::getDates($period['start']->format('Y-m-d'), $period['end']->format('Y-m-d'), $lesdayMask)
	: [];

if ($restrictToLesdays && empty($lesdays)) {
	$restrictToLesdays = false;
}

$lessons = [];
foreach ($lesdays as $lesday) {
	$lessons[] = $lesday->format('d/m/Y');
}

$firstLesDay = $period['start']->format('d/m/Y');
$lastLesDay = $period['end']->format('d/m/Y');
$userid = Factory::getApplication()->getIdentity()->id;
$joomlaToken = UserHelper::getProfile($userid)->get('joomlatoken');
$api_token = is_array($joomlaToken) ? (string) ($joomlaToken['token'] ?? '') : '';

$today = (new DateTime())->setTime(0, 0, 0);
$todayInRange = $today >= $period['start'] && $today <= $period['end'];

if ($restrictToLesdays && !in_array($today->format('d/m/Y'), $lessons, true)) {
	$todayInRange = false;
}

/** @var Joomla\CMS\Document\Document  */
$doc = $app->getDocument();
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $doc->getWebAssetManager();
$wa->registerAndUseStyle('lesson', 'media/com_balancirk/css/lesson.css')
    ->registerAndUseScript('bootstrap-datepicker', 'https://unpkg.com/bootstrap-datepicker@latest/dist/js/bootstrap-datepicker.min.js')
    ->registerAndUseScript('bootstrap-datepicker-nl', 'https://unpkg.com/bootstrap-datepicker@latest/dist/locales/bootstrap-datepicker.nl-BE.min.js')
    ->registerAndUseScript('teacher-script', 'media/com_balancirk/js/balancirk_teacher_date.js')
    ->addInlineScript(
        '
	var changed = false;
	jQuery(document).ready(function() {
		jQuery("#jform_date").datepicker({
			language: "nl-BE",
			startDate: "' . $firstLesDay . '",
    		endDate: "' . $lastLesDay . '",
			todayHighlight: true,  //Do not to forget to define class today
			todayBtn: true,
			maxViewMode: 0,
			weekStart: 1,
			beforeShowDay: function(date) {
				' . ($restrictToLesdays ? '
				var day = date.getDate();
				var month = date.getMonth() + 1;
				var year = date.getFullYear();
				day = (day < 10) ? "0" + day : day;
				month = (month < 10) ? "0" + month : month;
				var formattedDate = day + "/" + month + "/" + year;
				var lesdays = ["' . implode('","', $lessons) . '"];
				return jQuery.inArray(formattedDate, lesdays) > -1;
				' : 'return true;') . '
			},
			autoclose: true,
		})
	 ' . ($todayInRange ? 'jQuery("#jform_date").datepicker("setDate", "' . $today->format('d/m/Y') . '");
	 changed = true' : '') . '
	}); //ready
	'
    );
$doc->addScriptOptions('teacher-script', ['token' => $api_token]);

$teachers = $this->get('Teachers');
$data = [];
$data['id'] = $this->item->id;

$form = $this->get('teacherForm');
$form->bind($data);

foreach ($teachers as $teacher) {
    $form->getField('teachers')->addOption($teacher->firstname . " " . $teacher->name, ['value' => $teacher->id]);
}

$presence_url = Route::_('index.php?option=com_balancirk&view=lesson&layout=presence&id=' . (int) $this->item->id);
$url = Route::_('index.php?option=com_balancirk&view=lesson');
?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-edit-top}'); ?>
<form action="<?= $url ?>" method="POST" name="adminForm" id="teacher-form" class="form-validate">
	<div class="row">
		<div class="col-md-12">
			<h3><?= Text::_('COM_BALANCIRK_LESSONS_PRESENCES'); ?><?= $this->item->name ?></h3>
			<?= $form->renderField('id'); ?>
			<label for="lessonDate">Select Date:</label>
			<input type="text" id="jform_date" class="form-control" name="jform[date]" />

			<?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'teachers')); ?>

			<?= $form->getInput('teachers'); ?>
			<input type="hidden" name="task" />
		</div>
		<?= HTMLHelper::_('form.token'); ?>

		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-3">
				<button type="button" class="balancirk_button balancirk_presence_button" onclick="Joomla.submitbutton('lesson.teacher')">
					<?= Text::_('JSAVE') ?>
				</button>
			</div>
			<div class="col-12 col-md-3">
				<button type="button" class="balancirk_button balancirk_presence_button" onclick="Joomla.submitbutton('lesson.cancel')">
					<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
				</button>
			</div>
			<div class="col-12 col-md-3">
				<a class="balancirk_presence_button" href="<?= $presence_url ?>">
					<?= Text::_('COM_BALANCIRK_LESSON_PRESENCE') ?>
				</a>
			</div>
		</div>
	</div>
</form>
<!-- Modal alerting in case of changed values -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="confirmModalLabel">Confirm Date Change</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				Are you sure you want to change the date?
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmChange">Confirm</button>
			</div>
		</div>
	</div>
</div>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-edit-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
