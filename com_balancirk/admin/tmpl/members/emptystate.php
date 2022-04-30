<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo, Inc. All rights reserved.
 * @license     GNU General Public License version 3
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
    'textPrefix' => 'COM_BALANCIRK',
    'formURL' => 'index.php?option=com_balancirk',
    'icon' => 'icon-copy',
];
$user = Factory::getApplication()->getIdentity();
if ($user->authorise('core.create', 'com_balancirk') || count($user->getAuthorisedCategories('com_balancirk', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_balancirk&task=member.add';
}
echo LayoutHelper::render('joomla.content.emptystate', $displayData);
