<?php

/**
 * @package 	com_balancirk
 * @subpackage 	student
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die('Restricted access');
?>
<h1><?= Text::_('COM_BALANCIRK_LABEL_STUDENT') ?></h1>
<?php
echo $this->student->id;
