<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

/**
 * Legacy layout: SPA moved to view=spa.
 * Keep this file so old menu items still open the app.
 */
$app = Factory::getApplication();
$app->redirect(Route::_('index.php?option=com_balancirk&view=spa', false));
