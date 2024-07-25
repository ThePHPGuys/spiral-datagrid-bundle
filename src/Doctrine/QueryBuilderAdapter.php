<?php

declare(strict_types=1);

/*
 * Spiral DataGrid Bundle
 * Copyright (c) ThePHPGuys <https://thephpguys.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ThePhpGuys\SpiralDataGridBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;

readonly class QueryBuilderAdapter implements \IteratorAggregate, \Countable
{
    public function __construct(private QueryBuilder $queryBuilder)
    {
    }

    public function andWhereGroup(callable $scopeCallback): self
    {
        $scopeWhere = $this->scopedWhere($scopeCallback);
        if (null !== $scopeWhere) {
            $this->queryBuilder->andWhere($scopeWhere);
        }

        return $this;
    }

    public function orWhereGroup(callable $scopeCallback): self
    {
        $scopeWhere = $this->scopedWhere($scopeCallback);
        if (null !== $scopeWhere) {
            $this->queryBuilder->orWhere($scopeWhere);
        }

        return $this;
    }

    /**
     * @param \Closure(QueryBuilder $queryBuilder):void $queryBuilderCallback
     */
    public function withQueryBuilder(\Closure $queryBuilderCallback): self
    {
        $queryBuilderCallback($this->queryBuilder);

        return $this;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->queryBuilder->getQuery()->toIterable();
    }

    public function createAutoParameter(mixed $value, $type = null): string
    {
        $key = ':dg_p_'.$this->queryBuilder->getParameters()->count();
        $this->queryBuilder->setParameter($key, $value, $type);

        return $key;
    }

    private function scopedWhere(\Closure $scopeCallback): mixed
    {
        // Backup existing conditions
        $existingWhere = $this->queryBuilder->getDQLPart('where');
        $this->queryBuilder->resetDQLPart('where');
        $scopeCallback($this);
        $scopeWhere = $this->queryBuilder->getDQLPart('where');
        // Restore conditions
        if (null !== $existingWhere) {
            $this->queryBuilder->add('where', $existingWhere);
        } else {
            $this->queryBuilder->resetDQLPart('where');
        }

        return $scopeWhere;
    }

    public function count(): int
    {
        $countBuilder = clone $this->queryBuilder;
        $countBuilder->setMaxResults(null);
        $countBuilder->setFirstResult(null);

        $countBuilder->select('count('.$countBuilder->getRootAliases()[0].')');

        return $countBuilder->getQuery()->getSingleScalarResult();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return clone $this->queryBuilder;
    }
}
