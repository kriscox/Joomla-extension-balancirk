<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Host model for the Balancirk Angular SPA / PWA.
 *
 * @since  1.4.0
 */
class SpaModel extends BaseDatabaseModel
{
    /**
     * Return a lightweight item used by the SPA host template (page title).
     *
     * @return  object
     *
     * @since   1.4.0
     */
    public function getItem()
    {
        $app  = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        $item = new \stdClass();
        $item->title = $menu ? (string) $menu->title : 'Balancirk';

        return $item;
    }
}
