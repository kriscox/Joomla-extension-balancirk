<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 * 
 * @copyright   Copyright (C) 2023 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use CoCoCo\Component\Balancirk\Site\Model\LessonModel;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$lesdays = LessonModel::getDates($this->item->start, $this->item->end, LessonModel::getLesdays($this->item->lesdays));
$students = $this->get('Students');
$data = [];
$data['id'] = $this->item->id;

$form = $this->get('PresenceForm');
$form->bind($data);

foreach ($students as $student)
{
	$form->getField('students')->addOption($student->firstname . " " . $student->name, ['value' => $student->id]);
}
foreach ($lesdays as $date)
{
	$form->getField('date')->addOption($date->format("d/m/Y"), ['value' => $date->format("Y-m-d")]);
}

$url = Route::_('index.php?option=com_balancirk&view=lesson');
?>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-edit-top}'); ?>
<<form action="<?= $url ?>" method="POST" name="adminForm" id="presence-form" class="form-validate">
	<div class="row">
		<div class="col-md-12">
			<h3><?= Text::_('COM_BALANCIRK_LESSONS_PRESENCES'); ?><?= $this->item->name ?></h3>
			<?= $form->renderField('id'); ?>
			<?= $form->renderField('date'); ?>
			<?= $form->getInput('selected_dates'); ?>
			<input type="hidden" name="task" />
		</div>
		<?= HTMLHelper::_('form.token'); ?>

		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('lesson.presence')">
					<?= Text::_('JSAVE') ?>
				</button>
			</div>
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('lesson.cancel')">
					<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
				</button>
			</div>
		</div>
	</div>
	</form>
	<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-edit-bottom}'); ?>
	<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>