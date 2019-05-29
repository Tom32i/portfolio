<?php

namespace Content\EventListener;

use Content\Builder\RouteInfo;
use Content\Builder\Sitemap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Map all routes into a Sitemap
 */
class SitemapListener implements EventSubscriberInterface
{
    /**
     * Routes
     *
     * @var RouteCollection
     */
    private $routes;

    /**
     * Sitemap
     *
     * @var Sitemap
     */
    private $sitemap;

    /**
     * Constructor
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, Sitemap $sitemap)
    {
        $this->routes = RouteInfo::createFromRouteCollection($router->getRouteCollection());
        $this->sitemap = $sitemap;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::RESPONSE => 'onKernelReponse'];
    }

    /**
     * Handler Kernel Response events
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelReponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $route = $this->routes[$request->attributes->get('_route')];

        if ($route && $route->isMapped()) {
            $this->sitemap->add(
                $request->attributes->get('_canonical'),
                new \DateTime($response->headers->get('Last-Modified'))
            );
        }
    }
}
