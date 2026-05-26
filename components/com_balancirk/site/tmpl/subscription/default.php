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
use Joomla\CMS\Factory;
use Joomla\CMS\WebAsset\WebAssetManager;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$selectedStudentId = (int) $this->form->getValue('student');
$document = Factory::getApplication()->getDocument();
/** @var WebAssetManager $wa */
$wa = $document->getWebAssetManager();
$wa->registerAndUseScript('subscription-lessons', 'media/com_balancirk/js/balancirk_subscription_lessons.js');
$document->addScriptOptions('subscription-lessons', [
    'endpoint' => Route::_('index.php?option=com_balancirk&task=subscription.lessons&format=json', false),
    'errorMessage' => Text::_('COM_BALANCIRK_SUBSCRIPTION_LESSONS_LOAD_ERROR'),
    'placeholder' => Text::_('COM_BALANCIRK_SELECT_CHOOSE') . Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_LESSON'),
]);
?>

<?php echo HtmlHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HtmlHelper::_('content.prepare', '{loadposition balancirk-subscription-top}'); ?>
<?php if (empty($this->students)) : ?>
	<div class="alert alert-info">
		<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
		<?= Text::_('COM_BALANCIRK_NO_STUDENTS'); ?>
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
				<?php if ($this->hasOpenLessons) : ?>
					<?= $this->form->renderField('lesson'); ?>
				<?php endif; ?>
				<div id="subscription-lesson-notice" class="alert alert-info" <?= (!$this->hasOpenLessons || $selectedStudentId <= 0 || empty($this->lessons)) ? '' : 'hidden'; ?>>
					<?php
					if (!$this->hasOpenLessons) {
						echo Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SUBSCRIPTION');
					} elseif ($selectedStudentId <= 0) {
						echo Text::_('COM_BALANCIRK_SELECT_STUDENT_FOR_LESSONS');
					} elseif (empty($this->lessons)) {
						echo Text::_('COM_BALANCIRK_NO_LESSONS_FOR_SELECTED_STUDENT');
					}
					?>
				</div>
			</fieldset>
		</div>
		<input type="hidden" class="hidden" name="task" value="">
		<?= HTMLHelper::_('form.token'); ?>
		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" id="subscription-submit" onclick="Joomla.submitbutton('subscription.add')" <?= (!$this->hasOpenLessons || $selectedStudentId <= 0 || empty($this->lessons)) ? 'disabled' : ''; ?>>
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
