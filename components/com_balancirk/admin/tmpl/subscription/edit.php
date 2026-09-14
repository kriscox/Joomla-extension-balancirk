<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=subscription&layout=edit'); ?>"
	method="post" name="adminForm" id="subscription-form" class="form-validate">
	<div class="row">
		<div class="col-lg-8">
			<div class="card">
				<div class="card-body">
					<?= $this->form->renderField('student'); ?>
					<?= $this->form->renderField('lesson'); ?>
					<p class="text-muted"><?= Text::_('COM_BALANCIRK_SUBSCRIPTION_ADMIN_HELP'); ?></p>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" value="">
	<?php if (!empty($this->return)) : ?>
		<input type="hidden" name="return" value="<?= $this->escape($this->return); ?>">
	<?php endif; ?>
	<?= HTMLHelper::_('form.token'); ?>
</form>
