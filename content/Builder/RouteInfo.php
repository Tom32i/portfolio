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
     * Get content
     *
     * @return string
     */
    /*public function getContent()
    {
        return $this->route->getOption('content');
    }*/

    /**
     * Has content?
     *
     * @return boolean
     */
    /*public function hasContent()
    {
        return $this->route->hasOption('content');
    }*/

    /**
     * Is content list?
     *
     * @return boolean
     */
    /*public function isList()
    {
        return $this->route->getOption('list');
    }*/

    /**
     * Get index by
     *
     * @return string
     */
    /*public function getIndexBy()
    {
        return $this->route->getOption('index');
    }*/

    /**
     * Get sort order
     *
     * @return boolean
     */
    /*public function getOrder()
    {
        return $this->route->getOption('order');
    }*/

    /**
     * Is pagination enabled?
     *
     * @return boolean
     */
    /*public function isPaginated()
    {
        return $this->route->hasOption('page');
    }*/

    /**
     * Is visible?
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->route->getOption('visible') ?: $this->name[0] !== '_';
    }

    /**
     * Is Gettable
     *
     * @return boolean
     */
    public function isGettable()
    {
        $methods = $this->route->getMethods();

        return empty($methods) || in_array('GET', $methods);
    }

    /**
     * Is route on sitemap?
     *
     * @return boolean
     */
    public function isMapped()
    {
        return $this->isVisible() && $this->route->getOption('mapped') ?: true;
    }

    /**
     * Format
     *
     * @param string $format
     *
     * @return Route
     */
    public function setFormat($format)
    {
        $this
            ->value('_format', $format)
            ->assert('_format', $format);
    }

    /**
     * Set template
     *
     * @param string $template
     *
     * @return Route
     */
    public function template($template)
    {
        $this->value('_template', $template);
    }

    /**
     * Set number of contents per page
     *
     * @param integer $perPage
     *
     * @return Route
     */
    public function setPerPage($perPage)
    {
        $this->route->setOption('perPage', $perPage);
    }

    /**
     * Get number of contents per page
     *
     * @return integer
     */
    public function getPerPage()
    {
        return $this->route->getOption('perPage') ?: 10;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->route->getOption('_format') ?: 'html';
    }
}
