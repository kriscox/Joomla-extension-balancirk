<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$states = array(
	'0' => Text::_('COM_BALANCIRK_LESSON_STATUS_PAST'),
	'1' => Text::_('COM_BALANCIRK_LESSON_STATUS_CURRENT'),
	'2' => Text::_('COM_BALANCIRK_LESSON_STATUS_NEXT'),
	'-2' => Text::_('JTRASHED')
);

$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';
?>
<div class="row">
	<div class="col-md-12">
		<nav aria-label="Toolbar">
			<button class="button-new btn btn-success" type="button" onclick="Joomla.submitbutton('subscription.add')">
				<span class=" icon-new" aria-hidden="true"></span>
				<?= TEXT::_('COM_BALANCIRK_BUTTON_NEW') ?>
			</button>
			<a href="<?= Route::_('index.php?option=com_balancirk&view=students', false); ?>">
				<button class="btn btn-primary" type="button"><?= TEXT::_('COM_BALANCIRK_STUDENTS_LINK') ?></button>
			</a>
		</nav>
	</div>
</div>
<form action="<?= Route::_('index.php?option=com_balancirk&view=subscriptions'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('student.filter', array('view' => $this)); ?>

				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="subscriptionList">
						<caption id="captionTable">
							<?= Text::_('COM_BALANCIRK_SUBSCRIPTIONS_TABLE_CAPTION'); ?>
						</caption>
						<thead>
							<tr>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_STUDENT', 'a.firstname', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_LESSON', 'a.lesson', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_YEAR', 'a.year', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= TEXT::_('COM_BALANCIRK_SUBSCRIPTION_DELETE') ?>
							</tr>
							<?php $n = count($this->items);
							foreach ($this->items as $i => $item) : ?>
								<tr class="row<?= $i % 2; ?>">
									<td scope="row" class="d-none d-md-table-cell">
										<?= $this->escape(addslashes($item->firstname)); ?> <?= $this->escape(addslashes($item->name)) ?>
									</td>
									<td scope="row" class="d-none d-md-table-cell">
										<?= $this->escape(addslashes($item->lesson)); ?>
									</td>
									<td scope="row" class="d-none d-md-table-cell">
										<?= $this->escape(addslashes($item->year)); ?>
									</td>
									<td scope="row" class="d-none d-md-table-cell">
										<a href="<?= Route::_('index.php?option=com_balancirk&task=subscription.delete&id=' . $item->id); ?>">
											<span class="icon-purge"> </span>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
					</table>

				<?php endif; ?>
				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>