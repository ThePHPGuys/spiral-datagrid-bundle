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

use Spiral\DataGrid\GridInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DataGridResponse extends JsonResponse
{
    public function __construct(GridInterface $grid, int $status = 200, array $headers = [])
    {
        parent::__construct($this->createResponse($grid), $status, $headers);
    }

    private function createResponse(GridInterface $grid): array
    {
        $response = [];
        if (null !== $grid->getOption(GridInterface::PAGINATOR)) {
            $response['pagination'] = $grid->getOption(GridInterface::PAGINATOR);
        }

        if (isset($response['pagination']) && null !== $grid->getOption(GridInterface::COUNT)) {
            $response['pagination']['count'] = $grid->getOption(GridInterface::COUNT);
        }
        $response['data'] = iterator_to_array($grid->getIterator());

        return $response;
    }
}
