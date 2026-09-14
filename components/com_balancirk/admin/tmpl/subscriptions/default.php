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
?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=subscriptions'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span>
						<span class="visually-hidden"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="subscriptionList">
						<caption class="visually-hidden">
							<?= Text::_('COM_BALANCIRK_SUBSCRIPTIONS_TABLE_CAPTION'); ?>, <?= Text::_('JGLOBAL_SORTED_BY'); ?>
						</caption>
						<thead>
							<tr>
								<td class="w-1 text-center">
									<?= HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col" class="w-5 d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_ID', 'a.id', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_FIRSTNAME', 'a.firstname', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_NAME', 'a.name', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_LESSON', 'a.lesson', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_YEAR', 'a.year', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="w-10 text-center">
									<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_STATUS'); ?>
								</th>
								<?php if ($this->canDelete) : ?>
									<th scope="col" class="w-10 text-center">
										<?= Text::_('COM_BALANCIRK_SUBSCRIPTIONS_HEADING_ACTIONS'); ?>
									</th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->items as $i => $item) : ?>
								<tr class="row<?= $i % 2; ?>">
									<td class="text-center">
										<?= HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>
									<td class="d-none d-md-table-cell">
										<?= (int) $item->id; ?>
									</td>
									<td><?= $this->escape($item->firstname); ?></td>
									<td><?= $this->escape($item->name); ?></td>
									<td><?= $this->escape($item->lesson); ?></td>
									<td class="d-none d-md-table-cell"><?= $this->escape($item->year); ?></td>
									<td class="text-center">
										<?php if ((int) $item->subscribed === 1) : ?>
											<span class="badge bg-warning text-dark"><?= Text::_('COM_BALANCIRK_ON_WAITING_LIST'); ?></span>
										<?php else : ?>
											<span class="badge bg-success"><?= Text::_('COM_BALANCIRK_STATUS_SUBSCRIBED'); ?></span>
										<?php endif; ?>
									</td>
									<?php if ($this->canDelete) : ?>
										<td class="text-center">
											<button type="button"
												class="btn btn-sm btn-danger"
												onclick="if (confirm('<?= htmlspecialchars(Text::_('COM_BALANCIRK_SUBSCRIPTION_CONFIRM_DELETE'), ENT_QUOTES, 'UTF-8'); ?>')) { document.adminForm.task.value='subscription.delete'; document.adminForm.id.value='<?= (int) $item->id; ?>'; Joomla.submitform('subscription.delete'); }">
												<?= Text::_('COM_BALANCIRK_SUBSCRIPTION_REMOVE'); ?>
											</button>
										</td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?= $this->pagination->getListFooter(); ?>
				<?php endif; ?>
				<input type="hidden" name="task" value="">
				<input type="hidden" name="id" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
