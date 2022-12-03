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
$states = array(
	'0' => Text::_('COM_BALANCIRK_STATUS_SUBSCRIBED'),
	'1' => Text::_('COM_BALANCIRK_STATUS_UNSUBSCRIBED'),
	'2' => Text::_('JARCHIVED'),
	'-2' => Text::_('JTRASHED')
);

$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';
?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=students'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<!-- < ?= LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?> -->
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="studentList">
						<caption id="captionTable">
							<?= Text::_('COM_BALANCIRK_STUDENTS_TABLE_CAPTION'); ?>, <?= Text::_('JGLOBAL_SORTED_BY'); ?>
						</caption>
						<thead>
							<tr>
								<td style="width:1%" class="text-center">
									<?= HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_NAME', 'a.name', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="text_center d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_BIRTHDATE', 'a.birthdate', $listDirn, $listOrder); ?>
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
									<td scope="row" class="d-none d-md-table-cell">
										<a class="hasTooltip" href="<?= Route::_('index.php?option=com_balancirk&task=student.edit&id=' . $item->id); ?>">
											<?= $editIcon; ?><?= $this->escape(addslashes($item->firstname)); ?> <?= $this->escape(addslashes($item->name)) ?>
										</a>
									</td>
									<td scope="row" class="d-none d-md-table-cell">
										<?= HtmlHelper::date($item->birthdate, Text::_('DATE_FORMAT_FILTER_DATE')); ?>
									</td>
									<td class="article-status">
										<?= $states[$item->state]; ?>
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