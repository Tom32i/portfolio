<?php

declare(strict_types=1);

/*
 * This file is part of the Tribü project.
 *
 * Copyright © Tribü
 *
 * @author Elao <contact@elao.com>
 */

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class MenuExtension extends AbstractExtension
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getTests()
    {
        return [
            new TwigTest('currentRoot', [$this, 'isCurrentRoot']),
            new TwigTest('currentRoute', [$this, 'isCurrentRoute']),
        ];
    }

    public function isCurrentRoot(string $root): bool
    {
        return $this->getAttribute('_menu') === $root;
    }

    public function isCurrentRoute(string $route): bool
    {
        return $this->getAttribute('_route') === $route;
    }

    /**
     * Get attribute from current request
     *
     * @return string|array
     */
    private function getAttribute(string $name)
    {
        return $this->requestStack->getCurrentRequest()->attributes->get($name);
    }
}
