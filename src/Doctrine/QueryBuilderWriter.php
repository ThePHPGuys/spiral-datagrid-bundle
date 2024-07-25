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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Spiral\DataGrid\Compiler;
use Spiral\DataGrid\Exception\CompilerException;
use Spiral\DataGrid\Specification;
use Spiral\DataGrid\Specification\FilterInterface;
use Spiral\DataGrid\Specification\SorterInterface;
use Spiral\DataGrid\SpecificationInterface;
use Spiral\DataGrid\WriterInterface;

class QueryBuilderWriter implements WriterInterface
{
    protected const array COMPARE_OPERATORS = [
        Specification\Filter\Lte::class => '<=',
        Specification\Filter\Lt::class => '<',
        Specification\Filter\Equals::class => '=',
        Specification\Filter\NotEquals::class => '!=',
        Specification\Filter\Gt::class => '>',
        Specification\Filter\Gte::class => '>=',
    ];

    protected const array ARRAY_OPERATORS = [
        Specification\Filter\InArray::class => 'IN',
        Specification\Filter\NotInArray::class => 'NOT IN',
    ];

    // Sorter directions mapping
    protected const array SORTER_DIRECTIONS = [
        Specification\Sorter\AscSorter::class => 'ASC',
        Specification\Sorter\DescSorter::class => 'DESC',
    ];

    public function write(mixed $source, SpecificationInterface $specification, Compiler $compiler): mixed
    {
        /* @var QueryBuilderAdapter $source */
        return match (true) {
            !$this->targetAcceptable($source) => null,
            $specification instanceof FilterInterface => $this->writeFilter($source, $specification, $compiler),
            $specification instanceof SorterInterface => $this->writeSorter($source, $specification, $compiler),
            $specification instanceof Specification\Pagination\Limit => $source->withQueryBuilder(static function (QueryBuilder $queryBuilder) use ($specification) {
                $queryBuilder->setMaxResults($specification->getValue());
            }),
            $specification instanceof Specification\Pagination\Offset => $source->withQueryBuilder(static function (QueryBuilder $queryBuilder) use ($specification) {
                $queryBuilder->setFirstResult($specification->getValue());
            }),
            default => null
        };
    }

    /**
     * @psalm-assert-if-true QueryBuilderAdapter $target
     */
    protected function targetAcceptable(mixed $target): bool
    {
        if ($target instanceof QueryBuilderAdapter) {
            return true;
        }

        return false;
    }

    protected function writeFilter(QueryBuilderAdapter $source, FilterInterface $filter, Compiler $compiler): ?QueryBuilderAdapter
    {
        if ($filter instanceof Specification\Filter\All || $filter instanceof Specification\Filter\Map) {
            return $source->andWhereGroup(static function () use ($compiler, $filter, $source): void {
                $compiler->compile($source, ...$filter->getFilters());
            });
        }

        if ($filter instanceof Specification\Filter\Any) {
            return $source->andWhereGroup(static function () use ($compiler, $filter, $source): void {
                foreach ($filter->getFilters() as $subFilter) {
                    $source->orWhereGroup(static function () use ($compiler, $subFilter, $source): void {
                        $compiler->compile($source, $subFilter);
                    });
                }
            });
        }

        if ($filter instanceof Specification\Filter\Expression) {
            return $source->withQueryBuilder(function (QueryBuilder $queryBuilder) use ($filter, $source): void {
                $queryBuilder->andWhere(sprintf(
                    '%s %s %s',
                    $filter->getExpression(),
                    $this->getExpressionOperator($filter),
                    $this->getParameter($source, $filter)
                ));
            });
        }

        return null;
    }

    protected function writeSorter(QueryBuilderAdapter $source, SorterInterface $sorter, Compiler $compiler): ?QueryBuilderAdapter
    {
        if ($sorter instanceof Specification\Sorter\SorterSet) {
            foreach ($sorter->getSorters() as $subSorter) {
                $source = $compiler->compile($source, $subSorter);
            }

            return $source;
        }

        if (
            $sorter instanceof Specification\Sorter\AscSorter
            || $sorter instanceof Specification\Sorter\DescSorter
        ) {
            $direction = static::SORTER_DIRECTIONS[$sorter::class];
            foreach ($sorter->getExpressions() as $expression) {
                $source->withQueryBuilder(function (QueryBuilder $queryBuilder) use ($expression, $direction): void {
                    $queryBuilder->orderBy($expression, $direction);
                });
            }

            return $source;
        }

        return null;
    }

    protected function fetchValue(mixed $value): mixed
    {
        if ($value instanceof Specification\ValueInterface) {
            throw new CompilerException('Value expects user input, none given');
        }

        return $value;
    }

    protected function getExpressionOperator(Specification\Filter\Expression $filter): string
    {
        if ($filter instanceof Specification\Filter\Like) {
            return 'LIKE';
        }

        if ($filter instanceof Specification\Filter\InArray || $filter instanceof Specification\Filter\NotInArray) {
            return static::ARRAY_OPERATORS[$filter::class];
        }

        return static::COMPARE_OPERATORS[$filter::class];
    }

    protected function getParameter(QueryBuilderAdapter $adapter, Specification\Filter\Expression $filter): string
    {
        if ($filter instanceof Specification\Filter\Like) {
            return $adapter->createAutoParameter(sprintf($filter->getPattern(), $this->fetchValue($filter->getValue())));
        }

        if ($filter instanceof Specification\Filter\InArray || $filter instanceof Specification\Filter\NotInArray) {
            return sprintf('(%s)', $adapter->createAutoParameter($this->fetchValue($filter->getValue()), ArrayParameterType::STRING));
        }

        return $adapter->createAutoParameter($this->fetchValue($filter->getValue()));
    }
}
