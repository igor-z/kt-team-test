<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\ProductCategory;
use PHPUnit\Framework\TestCase;

class ProductCategoryTest extends TestCase
{
    public function testProducts(): void
    {
        $product = new Product();

        $category = new ProductCategory();
        $category->addProduct($product);

        $this->assertEquals([$product], $category->getProducts()->toArray());
    }
}