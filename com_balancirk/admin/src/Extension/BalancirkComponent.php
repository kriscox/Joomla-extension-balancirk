<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Administrator\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use CoCoCo\Component\Balancirk\Administrator\Service\HTML\AdministratorService;

/**
 * Component class for Balancirk.
 *
 * @since  0.0.1
 */
class BalancirkComponent extends MVCComponent implements
    BootableExtensionInterface,
    //    RouterServiceInterface,
    CategoryServiceInterface
{
    use CategoryServiceTrait;
    use HTMLRegistryAwareTrait;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     * 
     * @param ContainerInterface $container The Container 
     *
     * @return  void
     *
     * @since   0.0.1
     */
    public function boot(ContainerInterface $container)
    {
        $this->getRegistry()->register('balancirkadministrator', new AdministratorService);
    }
}
