<?php

    use Joomla\CMS\Language\Text;

    /**
    * @package     Joomla.Administrator
    * @subpackage  com_balancirk
    *
    * @copyright   CoCoCo
    * @license     Copyright (C)  GPL v2 All rights reserved.
    */

    // No direct access to this file
    defined('_JEXEC') or die('Restricted Access');
    ?>
    <!-- <h2><?= Text::_('COM_HELLOWORLD_MSG_HELLO_WORLD') ?></h2> -->
    <h2>Hello world!</h2>
    <h4>This is the initial site view.</h4>
    <p><?= $this->getModel()->getItem()->message; ?></p>
    