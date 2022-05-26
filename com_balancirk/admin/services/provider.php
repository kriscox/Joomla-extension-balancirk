<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
<<<<<<< HEAD
=======
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
>>>>>>> cc6c2a0 (Initial)
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use CoCoCo\Component\Balancirk\Administrator\Extension\BalancirkComponent;

/**
 * The members service provider.
 *
 * @since  0.0.1
 */
return new class implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   0.0.1
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new CategoryFactory('\\CoCoCo\Component\Balancirk'));
        $container->registerServiceProvider(new MVCFactory('\\CoCoCo\Component\Balancirk'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\CoCoCo\Component\Balancirk'));
<<<<<<< HEAD
=======

>>>>>>> cc6c2a0 (Initial)
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new BalancirkComponent($container->get(ComponentDispatcherFactoryInterface::class));
<<<<<<< HEAD
                $component->setRegistry($container->get(Registry::class));
=======

                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

>>>>>>> cc6c2a0 (Initial)
                return $component;
            }
        );
    }
};
