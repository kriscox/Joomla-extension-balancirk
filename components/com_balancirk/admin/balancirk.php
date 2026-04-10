<?php

/**
 * default program to run
 *
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check (optional, but useful)
if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_balancirk'))
{
	throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Register helper classes or other includes if needed
// E.g., require_once __DIR__ . '/helpers/balancirk.php';
