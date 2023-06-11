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

<?php if (empty($this->students)) : ?>
	<div class="alert alert-info">
		<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
		<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>

<?php else : ?>
	<form action="<?= Route::_('index.php?option=com_balancirk&view=subscription'); ?>" method="post" id="subscription-form" name="adminForm" class="form-validate">
		<div class="col col-md-6">
			<fieldset>
				<?= $this->form->getInput('student'); ?>
			</fieldset>
		</div>
		<div class="col col-md-6">
			<fieldset addfieldpath="com_balancirk/src/Field/">
				<?= $this->form->getInput('lesson'); ?>
			</fieldset>
		</div>
		<input type="hidden" class="hidden" name="task" value="">
		<?= HTMLHelper::_('form.token'); ?>
		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('subscription.add')">
					<span class="icon-delete"> <?= Text::_('JSAVE') ?> </span>
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
