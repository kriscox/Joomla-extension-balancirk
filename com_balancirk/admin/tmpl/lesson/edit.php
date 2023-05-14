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
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=lesson&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="lesson-form" class="form-validate">

	<?= LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div>
		<?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_BALANCIRK_LESSON_TAB_DETAILS')); ?>
		<div class="row">
			<div class="col-md-9">
				<div class="row">
					<div class="col-md-6">
						<?= $this->form->renderField('type'); ?>
						<?= $this->form->renderField('fee'); ?>
						<?= $this->form->renderField('year'); ?>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card card-light">
					<div class="card-body">
						<?= LayoutHelper::render('joomla.edit.global', $this); ?>
					</div>
				</div>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'adress', Text::_('COM_BALANCIRK_LESSON_TAB_DATES')); ?>
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="col-md-6">
						<?= $this->form->renderField('start'); ?>
						<?= $this->form->renderField('end'); ?>
						<?= $this->form->renderField('start_registration'); ?>
						<?= $this->form->renderField('end_registration'); ?>
					</div>
				</div>
			</div>
		</div>
		<?= HTMLHelper::_('uitab.endTab'); ?>

		<?= HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token'); ?>
</form>