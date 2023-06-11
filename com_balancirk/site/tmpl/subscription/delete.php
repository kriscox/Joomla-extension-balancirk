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
	<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
	<?php echo JHtml::_('content.prepare', '{loadposition balancirk-subscription-delete-top}'); ?>
	<form action="<?= Route::_('index.php?option=com_balancirk&view=subscription'); ?>" method="post" id="subscription-form" name="adminForm" class="form-validate">
		<div class="col col-md-6">
			<?= $this->form->getInput('student'); ?>
		</div>
		<div class="col col-md-6">
			<?= $this->form->getInput('lesson'); ?>
		</div>
		<input type="hidden" class="hidden" name="task" value="">
		<?= HTMLHelper::_('form.token'); ?>
		<div class="row title-alias form-vertical mb-3">
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('subscription.delete')">
					<span class="icon-delete"> <?= Text::_('JACTION_DELETE') ?> </span>
				</button>
			</div>
			<div class="col-12 col-md-6">
				<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('subscription.cancel')">
					<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
				</button>
			</div>
		</div>

	</form>
	<?php echo JHtml::_('content.prepare', '{loadposition balancirk-subscription-delete-bottom}'); ?>
	<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
<?php endif; ?>