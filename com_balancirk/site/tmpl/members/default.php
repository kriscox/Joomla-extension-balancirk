<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
?>

<alert>Not yet implemented</alert>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-members-top}'); ?>
<table class="table" id="memberList">
	<thead>
		<tr>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				ID
			</th>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				NAME
			</th>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				USERNAME
			</th>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				EMAIL
			</th>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				ADRESS
			</th>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				LOCATION
			</th>
			<th scope="col" style="width:10px" class="text-center d-none d-md-table-cell">
				PHONE
			</th>
			<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
				BLOCK
			</th>
			<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
				<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_SENDMAIL'); ?>
			</th>
			<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
				<?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_REGISTERDATE'); ?>
			</th>
			<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
				LASTLOGINDATE
			</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$n = count($this->items);

		foreach ($this->items as $i => $item) :
		?>
			<tr class="row<?= $i % 2; ?>">
				<td class="d-none d-md-table-cell text-center">
					<?= $item->id; ?>
				</td>
				<th scope="row" class="has-context">
					<?= $this->escape(addslashes($item->firstname)); ?> <?= $this->escape(addslashes($item->name)) ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->username); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->email); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->street) . " " .
						$this->escape($item->number) . " " .
						$this->escape($item->bus); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->postcode) . " " .
						$this->escape($item->city); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->phone); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->block); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->sendEmail); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->registerDate); ?>
				</th>
				<th scope="row" class="has-context">
					<?= $this->escape($item->lastvisitDate); ?>
				</th>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-members-bottom}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>