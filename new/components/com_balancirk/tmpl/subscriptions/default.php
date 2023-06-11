<?php

/**
 * @package     Joomla.site
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
			<button class="button-new btn btn-success" type="button" onclick="location.href = '/index.php?option=com_balancirk&view=subscription&layout=edit';">
				<span class=" icon-new" aria-hidden="true"></span>
				<?= TEXT::_('COM_BALANCIRK_BUTTON_NEW') ?>
			</button>
			<a href="<?= Route::_('index.php?option=com_balancirk&view=students', false); ?>">
				<button class="btn btn-primary" type="button"><?= TEXT::_('COM_BALANCIRK_STUDENTS_LINK') ?></button>
			</a>
		</nav>
	</div>
</div>
<form action="<?= Route::_('index.php?option=com_balancirk&view=subscription&layout=delete&'); ?>" method="post" name="adminForm" id="adminForm">
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
								<th style="width:1%" class="text-center d-none">
									<?= HTMLHelper::_('grid.checkall'); ?>
								</th>
								<th scope="col" class="text_center d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_STUDENT', 'a.firstname', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_LESSON', 'a.lesson', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_YEAR', 'a.year', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= Text::_('JDELETE'); ?>
								</th>
							</tr>
							<?php $n = count($this->items);

							foreach ($this->items as $i => $item) : ?>
								<tr class="row<?= $i % 2; ?>">
									<td class="text-center d-none">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>
									<td scope="row" class="d-md-table-cell">
										<?= $this->escape(addslashes($item->firstname)); ?> <?= $this->escape(addslashes($item->name)) ?>
									</td>
									<td scope="row" class="d-md-table-cell">
										<?= $this->escape(addslashes($item->lesson)); ?>
									</td>
									<td scope="row" class="d-none d-md-table-cell">
										<?= $this->escape(addslashes($item->year)); ?>
									</td>
									<td scope="row" class="d-none d-md-table-cell">
										<button id='<?= $item->id ?>' type='submit' name='submit' value='<?= $item->id ?>'>
											<span class="icon-purge" />
										</button>
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