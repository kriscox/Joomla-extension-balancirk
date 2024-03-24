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
use Joomla\CMS\Document\Document;

use CoCoCo\Component\Balancirk\Site\Model\LessonModel;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

JHtml::_('jquery.framework');

/** @var Joomla\CMS\Application $app */
$app = Factory::getApplication();

/** create list of lesdays */
$lessons = [];
$lesdays = LessonModel::getDates($this->item->start, $this->item->end, LessonModel::getLesdays($this->item->lesdays));
$firstLesDay = min($lesdays)->format('d/m/Y');
$lastLesDay = max($lesdays)->format('d/m/Y');
foreach ($lesdays as $lesday)
{
	array_push($lessons, $lesday->format('d/m/Y'));
}
$userid = Factory::getApplication()->getIdentity()->id;
$api_token = UserHelper::getProfile($userid)->get('joomlatoken')['token'];

/** @var Joomla\CMS\Document\Document  */
$doc = $app->getDocument();
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $doc->getWebAssetManager();
$wa->registerAndUseStyle('lesson', 'media/com_balancirk/css/lesson.css')
	->registerAndUseScript('bootstrap-datepicker', 'https://unpkg.com/bootstrap-datepicker@latest/dist/js/bootstrap-datepicker.min.js')
	->registerAndUseScript('bootstrap-datepicker-nl', 'https://unpkg.com/bootstrap-datepicker@latest/dist/locales/bootstrap-datepicker.nl-BE.min.js')
	->registerAndUseScript('lesson-script', 'media/com_balancirk/js/balancirk_lesson_date.js')
	->addInlineScript('
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
				// Get day, month, and year components
    			var day = date.getDate();
    			var month = date.getMonth() + 1; // Months are zero-based
				var year = date.getFullYear();

				// Add leading zeros if necessary
				day = (day < 10) ? "0" + day : day;
				month = (month < 10) ? "0" + month : month;

				// Create the formatted string
				var formattedDate = day + "/" + month + "/" + year;

				var lesdays = ["' . implode('","', $lessons) . '"];
				if (jQuery.inArray(formattedDate, lesdays) > -1) {
					return true;
				} else {
					return false;
				}
			}, // Do not forget to define class disabled
			autoclose: true,
		})
	})
	');
$doc->addScriptOptions('lesson-script', ['token' => $api_token]);

$students = $this->get('Students');
$data = [];
$data['id'] = $this->item->id;

$form = $this->get('PresenceForm');
$form->bind($data);

foreach ($students as $student)
{
	$form->getField('students')->addOption($student->firstname . " " . $student->name, ['value' => $student->id]);
}

$url = Route::_('index.php?option=com_balancirk&view=lesson');
?>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-edit-top}'); ?>
<form action="<?= $url ?>" method="POST" name="adminForm" id="presence-form" class="form-validate">
	<div class="row">
		<div class="col-md-12">
			<h3><?= Text::_('COM_BALANCIRK_LESSONS_PRESENCES'); ?><?= $this->item->name ?></h3>
			<?= $form->renderField('id'); ?>
			<label for="lessonDate">Select Date:</label>
			<input type="text" id="jform_date" class="form-control" name="jform[date]" />


			<?= $form->getInput('students'); ?>
			<input type="hidden" name="task" />
		</div>
		<?= HTMLHelper::_('form.token'); ?>

		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button balancirk_presence_button" onclick="Joomla.submitbutton('lesson.presence')">
					<?= Text::_('JSAVE') ?>
				</button>
			</div>
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button balancirk_presence_button" onclick="Joomla.submitbutton('lesson.cancel')">
					<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
				</button>
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
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-edit-bottom}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>