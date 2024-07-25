<?php

declare(strict_types=1);

/*
 * Spiral DataGrid Bundle
 * Copyright (c) ThePHPGuys <https://thephpguys.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ThePhpGuys\SpiralDataGridBundle\DependencyInjection\Compiler;

use Spiral\DataGrid\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class WriterPass implements CompilerPassInterface
{
    public function __construct(private string $writerTag)
    {
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(Compiler::class)) {
            return;
        }

        $definition = $container->getDefinition(Compiler::class);
        $taggedServices = $container->findTaggedServiceIds($this->writerTag);
        foreach ($taggedServices as $id => $tagAttributes) {
            $definition->addMethodCall('addWriter', [new Reference($id)]);
        }
    }
}
