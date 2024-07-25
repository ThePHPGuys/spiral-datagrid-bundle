<?php

declare(strict_types=1);

/*
 * Spiral DataGrid Bundle
 * Copyright (c) ThePHPGuys <https://thephpguys.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ThePhpGuys\SpiralDataGridBundle;

use Spiral\DataGrid\Compiler;
use Spiral\DataGrid\GridFactory;
use Spiral\DataGrid\GridFactoryInterface;
use Spiral\DataGrid\GridSchema;
use Spiral\DataGrid\InputInterface;
use Spiral\DataGrid\WriterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use ThePhpGuys\SpiralDataGridBundle\DependencyInjection\Compiler\WriterPass;
use ThePhpGuys\SpiralDataGridBundle\Doctrine\QueryBuilderWriter;
use ThePhpGuys\SpiralDataGridBundle\EventListener\DataGridAttributeListener;
use ThePhpGuys\SpiralDataGridBundle\Http\DataGridInput;

final class SpiralDataGridBundle extends AbstractBundle
{
    public const string WRITER_TAG = 'spiral_datagrid.writer';
    public const string SCHEMA_TAG = 'spiral_datagrid.grid.schema';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new WriterPass(self::WRITER_TAG));
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerForAutoconfiguration(WriterInterface::class)->addTag(self::WRITER_TAG);
        $builder->registerForAutoconfiguration(GridSchema::class)->addTag(self::SCHEMA_TAG)->setPublic(true);
        $builder->register(DataGridInput::class)->setAutowired(true);
        $builder->setAlias(InputInterface::class, DataGridInput::class);
        $builder->register(Compiler::class)->setAutowired(true);
        $builder->register(QueryBuilderWriter::class)->setAutoconfigured(true);
        $builder->register(GridFactory::class)->setAutowired(true);
        $builder->setAlias(GridFactoryInterface::class, GridFactory::class);

        $builder->register(DataGridAttributeListener::class)->setAutoconfigured(true)
            ->setArguments([new Reference('service_container'), new Reference(GridFactoryInterface::class)])

        ;
    }
}
