<?php

/**
 * @package 	com_balancirk
 * @subpackage 	member
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die('Restricted access');
?>
<h1><?= Text::_('COM_BALANCIRK_LABEL_MEMBER') ?></h1>
<?php
echo $this->member->id;
