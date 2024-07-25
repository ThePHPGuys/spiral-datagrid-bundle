<?php

declare(strict_types=1);

/*
 * Spiral DataGrid Bundle
 * Copyright (c) ThePHPGuys <https://thephpguys.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ThePhpGuys\SpiralDataGridBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Spiral\DataGrid\GridFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use ThePhpGuys\SpiralDataGridBundle\Attribute\DataGrid;
use ThePhpGuys\SpiralDataGridBundle\Doctrine\QueryBuilderAdapter;
use ThePhpGuys\SpiralDataGridBundle\Http\DataGridResponse;

final class DataGridAttributeListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly GridFactoryInterface $gridFactory
    ) {
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $this->resolveResult($event->getControllerResult());
        if (null === $result) {
            return;
        }

        /** @var DataGrid|null $attribute */
        $attribute = $event->controllerArgumentsEvent?->getAttributes(DataGrid::class)[0] ?? null;
        if (null === $attribute) {
            return;
        }

        $schema = $this->makeSchema($attribute);
        $factory = $this->resolveFactory($schema['factory']);
        if (method_exists($factory, 'withDefaults')) {
            $factory = $factory->withDefaults($schema['defaults']);
        }
        $grid = $factory->create($result, $schema['grid']);
        if (null !== $schema['view']) {
            $grid = $grid->withView($schema['view']);
        }

        $event->setResponse(new DataGridResponse($grid));
    }

    private function makeSchema(DataGrid $dataGrid): array
    {
        $schema = [
            'grid' => $this->container->get($dataGrid->grid),
            'view' => $dataGrid->view,
            'defaults' => $dataGrid->defaults,
            'factory' => $dataGrid->factory,
        ];

        if (\is_string($schema['view'])) {
            $schema['view'] = $this->container->get($schema['view']);
        }

        if ([] === $schema['defaults'] && method_exists($schema['grid'], 'getDefaults')) {
            $schema['defaults'] = $schema['grid']->getDefaults();
        }

        if (null === $schema['view'] && \is_callable($schema['grid'])) {
            $schema['view'] = $schema['grid'];
        }

        return $schema;
    }

    private function resolveResult(mixed $result): ?iterable
    {
        if ($result instanceof QueryBuilder) {
            $result = new QueryBuilderAdapter($result);
        }

        return is_iterable($result) ? $result : null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', -128],
        ];
    }

    private function resolveFactory(?string $factoryClass): GridFactoryInterface
    {
        if (null !== $factoryClass) {
            $factory = $this->container->get($factoryClass);
            if ($factory instanceof GridFactoryInterface) {
                return $factory;
            }
        }

        return $this->gridFactory;
    }
}
