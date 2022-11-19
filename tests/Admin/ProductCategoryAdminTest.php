<?php

declare(strict_types=1);

namespace App\Tests\Admin;

use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductCategoryAdminTest extends WebTestCase
{
    use AdminListAssertionTrait;
    use AdminFormAssertionTrait;

    public function testCreateGet(): void
    {
        $client = static::createClient();

        $this->loadDefaultCategories($client);

        $crawler = $client->request('GET', "/product-categories/create");

        $this->assertFormFields($crawler, [
            'Name',
        ]);
    }

    public function testCreatePost(): void
    {
        $client = static::createClient();

        $this->loadDefaultCategories($client);

        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);

        $client->request('POST', "/product-categories/create?uniqid=form", [
            'form' => [
                'name' => 'Test category',
            ],
        ]);

        $category = $productCategoryRepository->findOneBy(['name' => 'Test category']);
        self::assertNotEmpty($category);
    }

    public function testEditGet(): void
    {
        $client = static::createClient();

        $this->loadDefaultCategories($client);

        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);
        $category = $productCategoryRepository->findOneBy(['name' => 'Category 1']);

        $crawler = $client->request('GET', "/product-categories/{$category->getId()}/edit");

        $this->assertFormFields($crawler, [
            'Name',
        ]);
    }

    public function testEditPost(): void
    {
        $client = static::createClient();

        $this->loadDefaultCategories($client);

        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);
        $category = $productCategoryRepository->findOneBy(['name' => 'Category 1']);

        $client->request('POST', "/product-categories/{$category->getId()}/edit?uniqid=form", [
            'form' => [
                'name' => 'Test category',
            ],
        ]);

        $category = $productCategoryRepository->findOneBy(['id' => $category->getId()]);

        self::assertEquals('Test category', $category->getName());
    }

    public function testListEmpty(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/product-categories');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.sonata-ba-list'));
    }

    public function testList(): void
    {
        $client = static::createClient();

        $this->loadDefaultCategories($client);

        $crawler = $client->request('GET', '/product-categories');

        self::assertResponseIsSuccessful();
        $this->assertTableHeaders($crawler, [
            '',
            'Name',
            'Actions',
        ]);
        $this->assertTableRows($crawler, [
            ['', 'Category 1', 'Delete'],
            ['', 'Category 2', 'Delete'],
        ]);
    }

    /**
     * @dataProvider listSortingDataProvider
     */
    public function testListSorting(array $expectedRows, string $field, string $order): void
    {
        $client = static::createClient();

        $this->loadDefaultCategories($client);

        $crawler = $client->request('GET', '/product-categories', [
            'filter' => [
                '_sort_order' => $order,
                '_sort_by' => $field,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $this->assertTableRows($crawler, $expectedRows);
    }

    public function listSortingDataProvider(): Generator
    {
        yield [
            [
                ['', 'Category 2', 'Delete'],
                ['', 'Category 1', 'Delete'],
            ],
            'name',
            'DESC',
        ];
    }

    private function loadDefaultCategories(KernelBrowser $client): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $category1 = new ProductCategory();
        $category1->setName('Category 1');
        $em->persist($category1);

        $category2 = new ProductCategory();
        $category2->setName('Category 2');
        $em->persist($category2);

        $em->flush();
    }
}