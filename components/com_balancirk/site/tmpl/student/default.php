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

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-student-top}'); ?>
<?= LayoutHelper::render('student.fullname_state', $this); ?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=student&layout=default&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="student-form" class="form-validate">
	<div class="balancirk_student" style="margin:30px">
		<div class="row">
			<?= $this->item->street ?>
			<?= $this->item->number ?>
			<?= $this->item->bus ?>
		</div>
		<div class="row">
			<?= $this->item->postcode ?>
			<?= $this->item->city ?>
		</div>
		<div class="row">
			<?= $this->item->email ?>
			<?= $this->item->phone ?>
		</div>
		<div class="row">
			<?= $this->item->birthdate ?>
		</div>
		<div class="row">
			<?= $this->item->uitpas ?>
		</div>
		<div class="row">
			<?php if ($this->item->allow_photo)
			{
				print Text::_("COM_BALANCIRK_ALLOW_PHOTO_FULL");
			}
			else
			{
				print Text::_("COM_BALANCIRK_DISALLOW_PHOTO_FULL");
			}
			?>
		</div>
	</div>
	<input type="hidden" name="jform[id]" id="jform_id" value="<?= $this->item->id ?>">
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
	<div class="row title-alias form-vertical mb-3">
		<div class="col-12 col-md-6">
			<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('student.cancel')">
				<span class="icon-arrow-left-4"> <?= Text::_('JCLOSE') ?></span>
			</button>
		</div>
	</div>
</form>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-student-bottom}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>