<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$document = Factory::getApplication()->getDocument();
$wa = $document->getWebAssetManager();
$wa->registerAndUseScript(
    'balancirk-holiday-dates',
    'media/com_balancirk/js/balancirk_holiday_dates.js',
    ['version' => 'auto']
);
?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=holiday&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="holiday-form" class="form-validate">

	<div class="row">
		<div class="col-lg-9">
			<div class="card">
				<div class="card-body">
					<?= $this->form->renderField('id'); ?>
					<?= $this->form->renderField('summary'); ?>
					<?= $this->form->renderField('year'); ?>
					<div class="row">
						<div class="col-md-6">
							<?= $this->form->renderField('startDate'); ?>
						</div>
						<div class="col-md-6">
							<?= $this->form->renderField('endDate'); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
</form>
