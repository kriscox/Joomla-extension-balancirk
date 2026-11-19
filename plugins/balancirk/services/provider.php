<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.balancirk
 *
 * @copyright   Copyright (C) 2023 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\WebServices\Balancirk\Extension\Balancirk;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.3.27
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Balancirk::class, function (Container $container) {
                $plugin = new Balancirk(
                    (array) PluginHelper::getPlugin('webservices', 'balancirk')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
