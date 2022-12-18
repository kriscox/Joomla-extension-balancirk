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

$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';
JHtml::_('jquery.framework');
?>
<h1>Test</h1>
<div id="holidayContainer" class="calendar-container" />