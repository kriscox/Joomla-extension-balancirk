<?php

defined('_JEXEC') or die;

use Joomla\Router\Route;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgWebservicesBalancirk extends CMSPlugin
{
  protected $autoloadLanguage = true;

  public function onBeforeApiRoute(&$router)
  {
    // A nice granular way to do it.
    // new Route(['HTTP_METHOD'],  'arbitrary/pattern/string',                     '<CONTROLLER_NAME>.<PUBLIC_METHOD_NAME>',               [], $defaults)
    // Obviously substitute the COMPONENTNAME (lowercase no spaces), <CONTROLLER_NAME> as lowercase, & PUBLIC_METHOD_NAME as camelcase.
    // controllers are to be placed in [site_root]/api/components/com_balancirk/src/Controllers/<CONTROLLER_NAME>Controller.php
    $defaults   = array_merge(['public' => false], ['component' => 'com_balancirk']);

    $routes = [
      new Route(['GET'], 'v1/presence/:lesson', 'presences.getpresence', ['lesson' => '\d+'], $defaults),
      new Route(['GET'], 'v1/presence/:lesson/:date', 'presences.getpresence', ['lesson' => '\d+', 'date' => '\d{4}-\d{2}-\d{2}'], $defaults),
      new Route(['POST'], 'v1/presence/:lesson', 'presences.setpresence', ['lesson' => '(d+)'], $defaults),
      new Route(['GET'], 'v1/teacher/:lesson', 'teachers.getteacher', ['lesson' => '\d+'], $defaults),
      new Route(['GET'], 'v1/teacher/:lesson/:date', 'teachers.getteacher', ['lesson' => '\d+', 'date' => '\d{4}-\d{2}-\d{2}'], $defaults),
      new Route(['POST'], 'v1/teacher/:lesson', 'teachers.setteacher', ['lesson' => '(d+)'], $defaults),
      # Double, next lines is integrated with the createCRUDRoutes method. Check if it can be removed. The class subscriptioncontroller can afterwards also be removed.
      new Route(['DELETE'], 'v1/subscription/:id', 'subscription.delete', ['recordkey' => '\d+'], $defaults),
      new Route(['GET'], 'v1/members/me', 'members.getCurrentUser', ['recordkey' => '\d+'], $defaults),
    ];

    // A more generic way to do it.
    $router->createCRUDRoutes('v1/members', 'members', ['component' => 'com_balancirk']);
    $router->createCRUDRoutes('v1/lessons', 'lessons', ['component' => 'com_balancirk']);
    $router->createCRUDRoutes('v1/students', 'students', ['component' => 'com_balancirk']);
    $router->createCRUDRoutes('v1/subscriptions', 'subscriptions', ['component' => 'com_balancirk']);

    // Finally, register all specified routes with Joomla's webservices router.
    $router->addRoutes($routes);
  }
}
