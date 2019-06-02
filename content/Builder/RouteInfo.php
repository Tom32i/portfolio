<?php

namespace Content\Builder;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route info
 */
class RouteInfo
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, Route $route)
    {
        $this->name = $name;
        $this->route = $route;
    }

    static public function createFromRouteCollection(RouteCollection $collection)
    {
        $routes = [];

        foreach ($collection as $name => $route) {
            $routes[$name] = new static($name, $route);
        }

        return $routes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is visible?
     *
     * @return boolean
     */
    public function isVisible(): bool
    {
        return $this->route->getOption('visible') ?? $this->name[0] !== '_';
    }

    /**
     * Is Gettable
     *
     * @return boolean
     */
    public function isGettable(): bool
    {
        $methods = $this->route->getMethods();

        return empty($methods) || in_array('GET', $methods);
    }

    /**
     * Is route on sitemap?
     *
     * @return boolean
     */
    public function isMapped(): bool
    {
        return $this->route->getOption('mapped') ?? $this->isVisible();
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->route->getOption('_format') ?: 'html';
    }
}
