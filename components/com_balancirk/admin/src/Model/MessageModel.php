<?php

namespace CoCoCo\Component\Balancirk\Administrator\Model;

defined('_JEXEC') or die;

/* List of availabel model classes
use Joomla\CMS\MVC\Model\AdminModel
use Joomla\CMS\MVC\Model\BaseModel
use Joomla\CMS\MVC\Model\FormModel
use Joomla\CMS\MVC\Model\ItemModel
use Joomla\CMS\MVC\Model\ListModel
*/

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Text;

/**
* @package     Joomla.Administrator
* @subpackage  com_balancirk
*
* @copyright   CoCoCo
* @license     Copyright (C) GPL v2 All rights reserved.
*/

/**
* Hello World Message Model
* @since 0.0.1
*/
class MessageModel extends ItemModel
{
    /**
    * Returns a message for display
    * @param integer $pk Primary key of the "message item", currently unused
    * @return object Message object
    */
    public function getItem($pk = null): object
    {
        $item = new \stdClass();
        $item->message = "A message from the admin message model";
        /* $item->message = Text::_('COM_HELLOWORLD_MSG_GREETING'); */
        return $item;
    }

}
