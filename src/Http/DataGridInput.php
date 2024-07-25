<?php

declare(strict_types=1);

/*
 * Spiral DataGrid Bundle
 * Copyright (c) ThePHPGuys <https://thephpguys.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ThePhpGuys\SpiralDataGridBundle\Http;

use Spiral\DataGrid\InputInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class DataGridInput implements InputInterface
{
    private string $prefix = '';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function withNamespace(string $namespace): InputInterface
    {
        $clone = clone $this;
        $clone->prefix = $namespace;

        return $clone;
    }

    public function hasValue(string $option): bool
    {
        return $this->requestStack->getCurrentRequest()->query->has($this->prefix.$option);
    }

    public function getValue(string $option, mixed $default = null): mixed
    {
        $all = $this->requestStack->getCurrentRequest()->query->all();

        return $all[$this->prefix.$option] ?? $default;
    }
}
