<?php
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Router\Route;
use Joomla\CMS\Log\Log;

class PlgWebservicesBalancirk extends CMSPlugin
{
  protected $autoloadLanguage = true;

  public function onBeforeApiRoute(&$router)
  {
    // A nice granular way to do it.
    // new Route(['HTTP_METHOD'],  'arbitrary/pattern/string',                     '<CONTROLLER_NAME>.<PUBLIC_METHOD_NAME>',               [], $defaults)
    // Obviously substitute the COMPONENTNAME (lowercase no spaces), <CONTROLLER_NAME> as lowercase, & PUBLIC_METHOD_NAME as camelcase.
    // controllers are to be placed in [site_root]/api/components/com_balancirk/src/Controllers/<CONTROLLER_NAME>Controller.php

    // An 'Airport' component is assumed for the purposes of illustration, please modify this file to match your actual controller class names.
    // So the 'hangars' controller below would in fact be located at:  [site_root]/api/components/com_balancirk/src/Controllers/HangarsController.php
    // inside of it would be a public method called getHangarsByAirline() etc

    // An obvious example for ease of comprehension
    $defaults    = array_merge(['public' => false], ['component' => 'com_balancirk']);
    $routes = [
      /* My Useful GET routes */
      new Route(['GET'],   'v1/airport/hangars/by/airline/:airLineName',     'hangars.getHangarsByAirline',                ['airLineName'    => '(filter.+validation.+regex)'], $defaults),
      /* No filtration regex allows ALL patterns to pass through into Jinput on the controller side. */
      new Route(['GET'],  'v1/airport/hangar/by/id/:id',                          'hangars.getHangarById',                 ['id' => '(\d{1,9})'], $defaults),
      /* No filtration regex allows ALL patterns to pass through into Jinput on the controller side. */
      new Route(['GET'],  'v1/airport/lounges/by/airline/:airLineName',    'lounges.getLoungesByAirline',           [], $defaults),
      /* My Useful POST routes */
      /*
      * If no url parameter is specified then no checking is necessary!
      * Note: same rules apply as for GET routes above if you DID want to have parameters).
      *
      * In the POST example below you need to grab the POST body via: $req = json_decode( $this->input->json->getRaw() ); on the controller side
      * If you want an associative array use: $req = json_decode( $this->input->json->getRaw(), true ); on the controller side
      */
      new Route(['POST'],  'v1/airport/purchase/ticket',                     'tickets.purchaseTicket',               [], $defaults)
    ];
    // Finally, register all specified routes with Joomla's webservices router.
    $router->addRoutes($routes);
  }
}
        