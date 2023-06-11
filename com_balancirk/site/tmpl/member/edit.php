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

?>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-edit-top}'); ?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=member&layout=edit') ?>" method="post" name="adminForm" id="member-form" class="form-validate">
	<div>
		<div class="row registration-form">
			<?= $this->form->renderField('id'); ?>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('username'); ?>
			</div>
		</div>
		<div class="row registration-form">
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('firstname'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('name'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('street'); ?>
			</div>
			<div class="col-12 col-md-3">
				<?= $this->form->renderField('number'); ?>
			</div>
			<div class="col-12 col-md-3">
				<?= $this->form->renderField('bus'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('postcode'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('city'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('email'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('phone'); ?>
			</div>
			<div class="col-12 col-md-6">
				<?= $this->form->renderField('birthdate'); ?>
			</div>
		</div>
	</div>

	<div class="row title-alias form-vertical mb-3">
		<div class="col-12 col-md-6">
			<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('member.save')">
				<?= Text::_('JSAVE') ?>
			</button>
		</div>
		<div class="col-12 col-md-6">
			<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('member.cancel')">
				<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
			</button>
		</div>
	</div>

	<input type="hidden" name="task" />
	<?= HTMLHelper::_('form.token'); ?>
</form>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-edit-bottom}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>