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
								<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_ID', 'a.id', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_SUMMARY', 'a.summary', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:10%" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_HOLIDAY_FIELD_YEAR', 'a.year', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:15%" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_START_HOLIDAY', 'a.startDate', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:15%" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_END_HOLIDAY', 'a.endDate', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->items as $i => $item) : ?>
								<tr class="row<?= $i % 2; ?>">
									<td class="text-center">
										<?= HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>
									<td class="d-none d-md-table-cell text-center">
										<?= (int) $item->id; ?>
									</td>
									<th scope="row" class="has-context">
										<a class="hasTooltip" href="<?= Route::_('index.php?option=com_balancirk&task=holiday.edit&id=' . (int) $item->id); ?>">
											<?= $editIcon; ?> <?= $this->escape($item->summary); ?>
										</a>
									</th>
									<td class="d-none d-md-table-cell">
										<?= $this->escape($item->year); ?>
									</td>
									<td class="d-none d-md-table-cell">
										<?= $this->escape(HTMLHelper::_('date', $item->startDate, Text::_('DATE_FORMAT_LC4'), 'UTC')); ?>
									</td>
									<td class="d-none d-md-table-cell">
										<?= $this->escape(HTMLHelper::_('date', $item->endDate, Text::_('DATE_FORMAT_LC4'), 'UTC')); ?>
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
