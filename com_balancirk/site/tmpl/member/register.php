<?php

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>

<form action="<?= Route::_('index.php?option=com_balancirk&view=member&layout=register'); ?>" method="post" name="adminForm" id="member-form" class="form-validate">

    <div class="row title-alias form-vertical mb-3">
        <div class="col-12 col-md-6">
            <?= $this->form->renderField('username'); ?>
        </div>
        <div class="col-12 col-md-6" style="align-self:center">
            <button type="submit" class="balancirk_register" style="width: 90%;">Register</button>
        </div>
    </div>

    <div>
        <?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'details')); ?>

        <?= HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_BALANCIRK_MEMBER_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <?= $this->form->renderField('id'); ?>
                        <?= $this->form->renderField('firstname'); ?>
                        <?= $this->form->renderField('name'); ?>
                        <?= $this->form->renderField('email'); ?>
                        <?= $this->form->renderField('phone'); ?>
                        <?= $this->form->renderField('birthdate'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?= HTMLHelper::_('uitab.endTab'); ?>

        <?= HTMLHelper::_('uitab.addTab', 'myTab', 'adress', Text::_('COM_BALANCIRK_MEMBER_TAB_ADRESS')); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <?= $this->form->renderField('street'); ?>
                        <?= $this->form->renderField('number'); ?>
                        <?= $this->form->renderField('bus'); ?>
                        <?= $this->form->renderField('postcode'); ?>
                        <?= $this->form->renderField('municipality'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?= HTMLHelper::_('uitab.endTab'); ?>

        <?= HTMLHelper::_('uitab.endTabSet'); ?>
    </div>
    <input type="hidden" name="task" value="register">
    <?= HTMLHelper::_('form.token'); ?>
</form>