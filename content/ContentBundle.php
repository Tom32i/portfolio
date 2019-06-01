<?php

namespace Content;

use Content\DependencyInjection\Compiler\ContentManagerCompilerPass;
use Content\DependencyInjection\Compiler\TwigExtensionFixerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TwigExtensionFixerCompilerPass());
        $container->addCompilerPass(new ContentManagerCompilerPass());
    }
}
