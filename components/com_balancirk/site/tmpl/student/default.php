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
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-student-top}'); ?>
<?= LayoutHelper::render(
    'student.fullname_state',
    array(
        'view' => $this,
        'hasCurrentYearSubscription' => $this->hasCurrentYearSubscription,
    )
); ?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=student&layout=default&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="student-form" class="form-validate">
	<div class="balancirk_student" style="margin:30px">
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_NAME') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->firstname) ?> <?= $this->escape($this->item->name) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_STREET') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->street) ?> <?= $this->escape($this->item->number) ?><?php if (!empty($this->item->bus)) : ?> <?= $this->escape($this->item->bus) ?><?php endif; ?>, <?= $this->escape($this->item->postcode) ?> <?= $this->escape($this->item->city) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_EMAIL') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->email) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_PHONE') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->phone) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_BIRTHDATE') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->birthdate) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_MUTUALITY') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->mutuality) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_UITPAS') ?></strong></div>
			<div class="col-md-8"><?= $this->escape($this->item->uitpas) ?></div>
		</div>
		<div class="row mb-2">
			<div class="col-md-4"><strong><?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_ALLOW_PHOTO') ?></strong></div>
			<div class="col-md-8"><?= $this->item->allow_photo ? Text::_('COM_BALANCIRK_ALLOW_PHOTO') : Text::_('COM_BALANCIRK_DISALLOW_PHOTO') ?></div>
		</div>
	</div>
	<input type="hidden" name="jform[id]" id="jform_id" value="<?= $this->item->id ?>">
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
	<div class="row title-alias form-vertical mb-3">
		<div class="col-12 col-md-4">
			<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('student.cancel')">
				<span class="icon-arrow-left-4"> <?= Text::_('JCLOSE') ?></span>
			</button>
		</div>
		<div class="col-12 col-md-4">
			<button type="button" class="balancirk_button" onclick="window.location.href='<?= Route::_('index.php?option=com_balancirk&view=subscriptions&filter_student=' . (int) $this->item->id) ?>'">
				<span class="icon-list"> <?= Text::_('COM_BALANCIRK_SUBSCRIPTIONS_LINK') ?></span>
			</button>
		</div>
	</div>
</form>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-student-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
