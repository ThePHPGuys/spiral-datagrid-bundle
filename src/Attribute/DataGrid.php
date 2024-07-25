<?php

declare(strict_types=1);

/*
 * Spiral DataGrid Bundle
 * Copyright (c) ThePHPGuys <https://thephpguys.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ThePhpGuys\SpiralDataGridBundle\Attribute;

use Spiral\DataGrid\GridSchema;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final readonly class DataGrid
{
    /**
     * @param class-string<GridSchema> $grid
     */
    public function __construct(
        public string $grid,
        public ?string $view = null,
        public array $defaults = [],
        public ?string $factory = null
    ) {
    }
}
