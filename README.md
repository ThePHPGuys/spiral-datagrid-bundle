SpiralDataGridBundle
============
This bundle provides integration for [spiral/data-grid](https://spiral.dev/docs/component-data-grid) with your Symfony project. Inspired by `spiral/data-grid-bridge` package,  it offers the following features:

* Seamless integration of the DataGrid component into a Symfony environment
* Doctrine\ORM\QueryBuilder writer for constructing DQL based on filters.
* DataGrid attribute for controllers

**[DataGrid Component Documentation](https://spiral.dev/docs/component-data-grid)**

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
composer require thephpguys/spiral-datagrid-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require thephpguys/spiral-datagrid-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    ThePhpGuys\SpiralDataGridBundle\SpiralDataGridBundle::class => ['all' => true],
];
```

Usage
============

### Step 1: Create grid schema

Create a grid schema by defining filters, sorters, and pagination settings.  You can find more filters on the [DataGrid documentation page](https://spiral.dev/docs/component-data-grid/current/en) :
```php
namespace App\Grid;
use App\Entity\Product;
use Spiral\DataGrid\GridSchema;
use Spiral\DataGrid\Specification\Filter;
use Spiral\DataGrid\Specification\Pagination\PagePaginator;
use Spiral\DataGrid\Specification\Sorter\Sorter;
use Spiral\DataGrid\Specification\Value\StringValue;

final class ProductGrid extends GridSchema
{

    public function __construct()
    {
        $this->addFilter('search',  new Filter\Any(
            new Filter\Like('e.title'),
            new Filter\Equals('e.article')
        ));
        $this->addSorter('name',new Sorter('e.name'));
        $this->addSorter('article',new Sorter('e.article'));
        $this->setPaginator(new PagePaginator(10, [10, 20, 50, 100]));
    }

    public function withDefaults():array
    {
        return [
            GridFactory::KEY_SORT => ['name'=>'asc']
        ];
    }

    //If this method exists it will be used as response data transformer
    public function __invoke(Product $product):array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'article' => $product->getArticle(),
        ];
    }
}
```

### Step 2: Register schema as service

Register your grid schema as a service in `config/services.yaml`:
```yaml
#config/services.yaml

services:
    App\Grid\ProductGrid:
```

### Step 3: Create controller method
Create a controller method to use the DataGrid:
```php
use App\Entity\Product;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use ThePhpGuys\SpiralDataGridBundle\Attribute\DataGrid

//...
#[Route('/products')]
#[DataGrid(grid: ProductGrid::class)]
public function productsList(EntityManagerInterface $entityManager):QueryBuilder
{
    return $entityManager->createQueryBuilder()->select('*')->from('e',Product::class);
} 
//...

```

This setup provides a route with JSON response and query parameters for filters, sorters, and pagination.

### Example Usage

* Filters: https://localhost/products?filter[search]=IPhone
* Sorters: https://localhost/products?sort[article]=desc
* Pagination: https://localhost/products?page=2


