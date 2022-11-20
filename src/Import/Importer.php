<?php

declare(strict_types=1);

namespace App\Import;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class Importer
{
    private array $cachedCategories = [];

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductCategoryRepository $productCategoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly int $batchSize,
    ) {
    }

    /**
     * @param iterable<ImportRow> $rows
     */
    public function import(iterable $rows): void
    {
        $this->cachedCategories = [];
        $i = 0;
        foreach ($rows as $row) {
            $i++;

            $product = $this->productRepository->findOneBy(['name' => $row->name]);
            if (!$product) {
                $product = new Product();
                $product->setName($row->name);
                $this->entityManager->persist($product);
            }

            $product->setDescription($row->description);
            $product->setCategory($this->getOrCreateCategory($row->category));
            $product->setWeight($row->weight);

            if ($i % $this->batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->cachedCategories = [];
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->cachedCategories = [];
    }

    private function getOrCreateCategory(string $categoryName): ProductCategory
    {
        if (isset($this->cachedCategories[$categoryName])) {
            return $this->cachedCategories[$categoryName];
        }

        $category = $this->productCategoryRepository->findOneBy([
            'name' => $categoryName,
        ]);

        if (!$category) {
            $category = new ProductCategory();
            $category->setName($categoryName);
            $this->entityManager->persist($category);
        }

        $this->cachedCategories[$categoryName] = $category;

        return $category;
    }
}