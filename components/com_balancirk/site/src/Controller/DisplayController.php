<?php

namespace CoCoCo\Component\Balancirk\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   CoCoCo
 * @license     Copyright (C)  2022 GPL v2 All rights reserved.
 */

/**
 * Balancirk Component Controller
 * @since  1.2.5
 */
class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = array())
    {
        $this->addPwaBaseAssets();

        parent::display($cachable, $urlparams);

        return $this;
    }

    private function addPwaBaseAssets(): void
    {
        $doc = Factory::getApplication()->getDocument();
        $root = rtrim(Uri::root(), '/');

        $doc->addCustomTag('<link rel="manifest" href="' . $root . '/media/com_balancirk/manifest.webmanifest">');
        $doc->addCustomTag('<meta name="theme-color" content="#1f3c88">');
        $doc->addScript($root . '/media/com_balancirk/js/balancirk_pwa_init.js');
    }
}
