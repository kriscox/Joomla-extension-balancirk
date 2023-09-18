<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>

<div class="row">
	<div class="col-md-12">
		<div id="j-main-container" class="j-main-container">
			<?php if (empty($displayData)) : ?>
				<div class="alert alert-info">
					<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
					<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php else : ?>
				<table class="table" id="studentList">
					<caption id="captionTable">
						<?= Text::_('COM_BALANCIRK_STUDENTS_TABLE_CAPTION'); ?>
					</caption>
					<thead>
						<tr>
							<td style="width:1%" class="text-center d-none">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="text_center d-md-table-cell">
								<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_NAME'); ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_BIRTHDATE'); ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_LAST_PRESENT'); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$n = count($displayData);

						foreach ($displayData as $i => $student) : ?>
							<tr class="row<?= $i % 2; ?>">
								<td class="text-center d-none">
									<?php echo HTMLHelper::_('grid.id', $i, $student->id); ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<?= $this->escape(addslashes($student->firstname)); ?> <?= $this->escape(addslashes($student->name)) ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<?= HtmlHelper::date($student->birthdate, Text::_('DATE_FORMAT_FILTER_DATE')); ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<? if ($student->last_presence === null) :
										echo Text::_('COM_BALANCIRK_LESSON_NEVER');
									else :
										echo HtmlHelper::date($student->last_presence, Text::_('DATE_FORMAT_FILTER_DATE'));
									endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>