<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.balancirk
 *
 * @copyright   Copyright (C) 2023 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace Joomla\Plugin\WebServices\Balancirk\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Services adapter for com_balancirk.
 *
 * @since  1.3.0
 */
final class Balancirk extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.3.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.3.27
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    /**
     * Registers com_balancirk API routes in the application.
     *
     * @param   BeforeApiRouteEvent  $event  The event object
     *
     * @return  void
     *
     * @since   1.3.0
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router = $event->getRouter();

        $defaults = ['public' => false, 'component' => 'com_balancirk'];
        $publicDefaults = ['public' => true, 'component' => 'com_balancirk'];

        $routes = [
            new Route(['GET'], 'v1/presence/:lesson', 'presences.getpresence', ['lesson' => '\d+'], $defaults),
            new Route(
                ['GET'],
                'v1/presence/:lesson/:date',
                'presences.getpresence',
                ['lesson' => '\d+', 'date' => '\d{4}-\d{2}-\d{2}'],
                $defaults
            ),
            new Route(['POST'], 'v1/presence/:lesson', 'presences.setpresence', ['lesson' => '\d+'], $defaults),
            new Route(['GET'], 'v1/teacher/:lesson', 'teachers.getteacher', ['lesson' => '\d+'], $defaults),
            new Route(
                ['GET'],
                'v1/teacher/:lesson/:date',
                'teachers.getteacher',
                ['lesson' => '\d+', 'date' => '\d{4}-\d{2}-\d{2}'],
                $defaults
            ),
            new Route(['POST'], 'v1/teacher/:lesson', 'teachers.setteacher', ['lesson' => '\d+'], $defaults),
            new Route(['GET'], 'v1/subscriptions/open-lessons', 'subscriptions.openlessons', [], $defaults),
            new Route(
                ['GET'],
                'v1/subscriptions/open-lessons/:student',
                'subscriptions.openlessons',
                ['student' => '\d+'],
                $defaults
            ),
            new Route(['GET'], 'v1/subscriptions/accounting-export', 'subscriptions.accountexport', [], $defaults),
            // Also covered by createCRUDRoutes; kept for the dedicated SubscriptionController delete path.
            new Route(['DELETE'], 'v1/subscription/:id', 'subscription.delete', ['id' => '\d+'], $defaults),
            new Route(['GET'], 'v1/members/me', 'members.getCurrentUser', ['recordkey' => '\d+'], $defaults),
            new Route(['GET'], 'v1/members/me/students', 'members.getmystudents', [], $defaults),
            new Route(['GET'], 'v1/members/relations', 'members.getrelations', [], $defaults),
            new Route(['PUT', 'PATCH'], 'v1/members/me', 'members.updateme', [], $defaults),
            new Route(['POST'], 'v1/members/register', 'members.register', [], $publicDefaults),
            new Route(['GET'], 'v1/settings', 'settings.getsettings', [], $defaults),
            new Route(['POST'], 'v1/settings', 'settings.savesettings', [], $defaults),
            new Route(['GET'], 'v1/settings/public', 'settings.getpublicsettings', [], $publicDefaults),
            new Route(['GET'], 'v1/lessons/:id/waitlist', 'lessons.getWaitlist', ['id' => '\d+'], $defaults),
            new Route(['POST'], 'v1/lessons/:id/waitlist/settle', 'lessons.settleWaitlist', ['id' => '\d+'], $defaults),
            new Route(['POST'], 'v1/lessons/:id/registration/close', 'lessons.closeRegistration', ['id' => '\d+'], $defaults),
            new Route(['POST'], 'v1/lessons/:id/registration/reopen', 'lessons.reopenRegistration', ['id' => '\d+'], $defaults),
            new Route(
                ['GET'],
                'v1/lessons/:id/cancellation/preview',
                'lessons.previewCancellation',
                ['id' => '\d+'],
                $defaults
            ),
            new Route(['POST'], 'v1/lessons/:id/cancellation', 'lessons.cancelLesson', ['id' => '\d+'], $defaults),
        ];

        $router->createCRUDRoutes('v1/members', 'members', ['component' => 'com_balancirk']);
        $router->createCRUDRoutes('v1/lessons', 'lessons', ['component' => 'com_balancirk']);
        $router->createCRUDRoutes('v1/students', 'students', ['component' => 'com_balancirk']);
        $router->createCRUDRoutes('v1/subscriptions', 'subscriptions', ['component' => 'com_balancirk']);

        $router->addRoutes($routes);
    }
}
