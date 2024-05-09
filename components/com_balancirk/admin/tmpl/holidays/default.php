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

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';
?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=holidays'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="holidayslist">
						<caption id="captionTable">
							<?= Text::_('COM_BALANCIRK_HOLIDAYS_TABLE_CAPTION'); ?>, <?= Text::_('JGLOBAL_SORTED_BY'); ?>
						</caption>
						<thead>
							<tr>
								<td style="width:1%" class="text-center">
									<?= HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_ID', 'a.id', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_YEAR', 'a.year', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_START_HOLIDAY', 'a.start', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_END_HOLIDAY', 'a.end', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
									<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_SUMMARY'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$n = count($this->items);
							foreach ($this->items as $i => $item) :
							?>
								<tr class="row<?= $i % 2; ?>">
									<td class="text-center">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>
									<td scope="row" class="has-context">
										<a class="hasTooltip" href="<?= Route::_('index.php?option=com_balancirk&task=holiday.edit&id=' . $item->id); ?>">
											<?= $editIcon; ?> <?= $this->escape(addslashes($item->id)) ?>
										</a>
									</td>
									<td scope="row" class="has-context">
										<?= $this->escape($item->year); ?>
									</td>
									<td scope="row" class="has-context">
										<?= $this->escape($item->startDate); ?>
									</td>
									<td scope="row" class="has-context">
										<?= $this->escape($item->endDate); ?>
									</td>
									<td scope="row" class="has-context">
										<?= $this->escape($item->summary); ?>
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