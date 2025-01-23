<?php
//phpcs:ignore Squiz.Functions.MultiLineFunctionDeclaration.BraceOnSameLine
//phpcs:ignore Squiz.Arrays.ArrayDeclaration.CloseBraceNewLine


/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$total_number_of_subscriptions = 0;

/** @var Joomla\CMS\Application $app */
$app = Factory::getApplication();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('lesson', 'media/com_balancirk/css/lesson.css')
?>

<alert>Not yet implemented</alert>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-lessons-top}'); ?>
<form action=" <?= Route::_('index.php?option=com_balancirk&view=lessons'); ?>" method="post" name="adminForm" id="adminForm">
	<?= LayoutHelper::render('joomla.searchtools.bar', array('view' => $this)); ?>
	<!-- Year Filter Dropdown -->

	<div class="row">
		<nav aria-label="Toolbar" style="display: flex; align-items: center;">
			<?= LayoutHelper::render(
			    'joomla.searchtools.default.bar',
			    array('view' => $this),
			    '',
			    array('filtersHidden' => ['filter_published'])
			); ?>
			<div class="js-stools" role="search">
				<div class="js-stools-container-bar">
					<div class="btn-toolbar">
						<label for="filter_year" class="filter_year"><?= Text::_('COM_BALANCIRK_FILTER_BY_YEAR'); ?></label>
						<select name="filter[year]" id="filter_year" class="form-control" onchange="document.adminForm.submit();">
							<?php foreach ($this->years as $year) : ?>
								<option value="<?= $year; ?>" <?= $this->state->get('filter.year') == $year ? 'selected="selected"' : ''; ?>>
									<?= $year; ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</nav>
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="lessonsList">
						<caption>
							<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_LESSONS'); ?>
							<?= $this->pagination->getListFooter();  ?>
						</caption>
						<thead>
							<tr>
								<th scope="col" class="hidden d-none d-md-table-cell">
									<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_ID'); ?>
								</th>
								<th scope="col" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_NAME', 'a.name', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_TYPE', 'a.type', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_YEAR', 'a.year', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_SUBSCRIPTIONS', 'a.numberOfStudents', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_STATE', 'a.state', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php $n = count($this->items);
				    foreach ($this->items as $i => $item) : ?>
								<tr class="row<?= $i % 2; ?>">
									<td class="hidden d-none d-md-table-cell text-center">
										<?= $item->id; ?>
									</td>
									<th scope="row" class="has-context">
										<?php if ($this->canDo->get('lessons.register')) : ?>
											<a class="hasTooltip" href="<?= Route::_('index.php?option=com_balancirk&view=lesson&layout=default&id=' . $item->id); ?>">
											<?php endif ?>

											<?= $this->escape(addslashes($item->name)) ?>

											<?php if ($this->canDo->get('lessons.register')) : ?>
											</a>
										<?php endif ?>
									</th>
									<th scope="row" class="has-context">
										<?= $this->escape($item->type); ?>
									</th>
									<th scope="row" class="has-context">
										<?= $this->escape($item->year); ?>
									</th>
									<th scope="row" class="has-context">
										<?php $total_number_of_subscriptions += $item->numberOfStudents; ?>
										<?= $this->escape($item->numberOfStudents); ?> &#47; <?= $this->escape($item->max_students); ?>
									</th>
									<th scope="row" class="has-context">
										<?= $this->escape($item->state); ?>
									</th>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<div class="col-md-6">
					<div class="row" id="jform">
						<div class="col-md-4" id="label"> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_SUBSCRIPTIONS') ?> </div>
						<div class="col-md-8" id="value"> <?= $total_number_of_subscriptions ?> </div>
					</div>
				</div>
				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
	<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-lessons-bottom}'); ?>
	<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>