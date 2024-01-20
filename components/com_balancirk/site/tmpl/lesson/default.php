<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

/** @var Joomla\CMS\Application $app */
$app = Factory::getApplication();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('lesson', 'media/com_balancirk/css/lesson.css');

$url = Route::_('index.php?option=com_balancirk&view=lesson&layout=default&id=' . (int) $this->item->id);
$presence_url = Route::_('index.php?option=com_balancirk&view=lesson&layout=presence&id=' . (int) $this->item->id);
?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-lesson-top}'); ?>
<form action="<?= $url ?>" method="post" name="adminForm" id="lesson-form" class="form-validate">
	<div class="m-t-2 m-b-3">
		<div class="control-group">
			<h3><?= $this->item->name ?></h3>
		</div>
	</div>
	<div>
		<?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'students')); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'students', Text::_('COM_BALANCIRK_LESSON_TAB_STUDENTS')); ?>
		<?= LayoutHelper::render('students.list', $this->students); ?>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_BALANCIRK_LESSON_TAB_DETAILS')); ?>
		<div class="row">
			<div class="col-md-6">
				<div class="row" id="jform">
					<div class="col-md-4" id="label" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_NUMBER_OF_STUDENTS') ?> </div>
					<div class="col-md-8" id="value" id="value"> <?= $this->item->numberOfStudents ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_TYPE') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->type ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_FEE') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->fee ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_YEAR') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->year ?> </div>
				</div>
				<div class="row">
					<button type="button" class="balancirk_button" onclick="location.href='<?= $presence_url ?>'" style="width: auto;">
						<?= Text::_('COM_BALANCIRK_LESSON_PRESENCE') ?>
					</button>
				</div>
			</div>
			<div class=" col-md-6">
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_START') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->start ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_END') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->end ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_START_REGISTRATION') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->start_registration ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_END_REGISTRATION') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->end_registration ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_STATE') ?> </div>
					<div class="col-md-8" id="value"> <?= $this->item->state ?> </div>
				</div>
				<div class="row" id="jform">
					<div class="col-md-4" id="label_lesdagen"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_LESDAYS') ?> </div>
					<div class="col-md-8" id="value_lesdagen">
						<?php if ($this->lesdays['Monday'] == 1) : ?>
							<?= Text::_('MONDAY') ?>,
						<?php endif ?>
						<?php if ($this->lesdays['Tuesday'] == 1) : ?>
							<?= Text::_('TUESDAY') ?>,
						<?php endif ?>
						<?php if ($this->lesdays['Wednesday'] == 1) : ?>
							<?= Text::_('WEDNESDAY') ?>,
						<?php endif ?>
						<?php if ($this->lesdays['Thursday'] == 1) : ?>
							<?= Text::_('THURSDAY') ?>,
						<?php endif ?>
						<?php if ($this->lesdays['Friday'] == 1) : ?>
							<?= Text::_('FRIDAY') ?>,
						<?php endif ?>
						<?php if ($this->lesdays['Saturday'] == 1) : ?>
							<?= Text::_('SATURDAY') ?>,
						<?php endif ?>
						<?php if ($this->lesdays['Sunday'] == 1) : ?>
							<?= Text::_('SUNDAY') ?>
						<?php endif ?>
					</div>
				</div>
			</div>
			<?= HTMLHelper::_('uitab.endTab'); ?>
			<?= HTMLHelper::_('uitab.endTabSet'); ?>
			<input type="hidden" name="task" value="">
			<?= HTMLHelper::_('form.token'); ?>
		</div>
</form>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-lesson-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>