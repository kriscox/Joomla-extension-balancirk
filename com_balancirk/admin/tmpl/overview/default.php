<?php

/**
 * @package 	com_balancirk
 * @subpackage 	student
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;



defined('_JEXEC') or die('Restricted access');
?>
<h1><?= Text::_('COM_BALANCIRK_NAME') ?></h1>

<h2><?= Text::_('COM_BALANCIRK_MENU') ?></h2>

<a href="<?php echo Route::_(RouteHelper::getFooRoute($item->slug, $item->catid, $item->language)); ?>">