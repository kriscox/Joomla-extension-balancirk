<?php

use Joomla\CMS\Language\Text;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_Balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<h2><?= Text::_('COM_BALANCIRK_NAME') ?></h2>

<p><?= $this->getModel()->getItems()->Name; ?></p>