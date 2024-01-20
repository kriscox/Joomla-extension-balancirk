<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;
?>

<div class="page-header">
	<h1>
		<?= $this->item->title; ?>
	</h1>
</div>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-top}'); ?>

Hello <?php echo $this->member; ?>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-member-bottom}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>