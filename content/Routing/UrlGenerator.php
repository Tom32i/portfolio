<?php

namespace Content\Routing;

use Content\Builder\PageList;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UrlGenerator implements UrlGeneratorInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator, PageList $pageList)
    {
        $this->urlGenerator = $urlGenerator;
        $this->pageList = $pageList;
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $this->pageList->add(
            $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context)
    {
        $this->urlGenerator->setContext($context);
    }

    public function getContext()
    {
        return $this->urlGenerator->getContext();
    }
}
