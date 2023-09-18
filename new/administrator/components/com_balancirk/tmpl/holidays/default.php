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
use Joomla\CMS\HTML\HTMLHelper;

$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';
JHtml::_('jquery.framework');
?>
<form action="<?= Route::_('index.php?option=com_balancirk&view=holidays'); ?>" method="post" name="holidaysForm" id="holidays-form" class="form-validate">
	<div id="holidayContainer" class="calendar-container">
	</div> <!-- placeholder for the agenda -->
	<input type="hidden" name="events" id="events">
	<input type="hidden" name="task" value>
	<?= HTMLHelper::_('form.token'); ?>
</form>