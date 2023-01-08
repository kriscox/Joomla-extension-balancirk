<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

defined('_JEXEC') or die;

HTMLHelper::_('behavior.keepalive');
?>

<?php if (empty($this->students)) : ?>
	<div class="alert alert-info">
		<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
		<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
<?php endif; ?>

<form>
	<div class="col col-md-6">
		<div class="row">
			<?php foreach ($this->students as $student) : ?>
				<input type="checkbox" class="form-check-input" name="<?php $student ?> value=" <?php $student ?>>
				<label class="form-check-label"><?php $student ?> </label>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="col col-md-6">
		<div class="row">
			<label class="col-form-label">
				<?= Text::_('COM_BALANCIRK_TABLE_HEADER_LESSON'); ?>
			</label>
			<div class="input-group mb-3">
				<div class="input-group-prepend">
					<label class="input-group-text" for="inputGroupSelect01">Options</label>
				</div>
				<select class="custom-select" id="inputGroupSelect01">
					<option selected>Choose...</option>
					<?php foreach ($this->lessons as $lesson) : ?>
						<option value="<?= $lesson ?>"> <?= $lesson ?> </option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
</form>