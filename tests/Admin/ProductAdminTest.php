<?php

namespace App\Tests\Admin;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Weight;
use App\Enum\WeightUnit;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductAdminTest extends WebTestCase
{
    use AdminListAssertionTrait;
    use AdminFormAssertionTrait;

    public function testEditGet(): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $productRepository = $client->getContainer()->get(ProductRepository::class);
        $product = $productRepository->findOneBy(['name' => 'Product 1']);

        $crawler = $client->request('GET', "/products/{$product->getId()}/edit");

        self::assertResponseIsSuccessful();

        $this->assertFormFields($crawler, [
            'Name',
            'Weight',
            'Category',
            'Description',
        ]);
    }

    public function testEditPost(): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);
        $category2 = $productCategoryRepository->findOneBy(['name' => 'Category 2']);

        $productRepository = $client->getContainer()->get(ProductRepository::class);
        $product = $productRepository->findOneBy(['name' => 'Product 1']);

        $client->request('POST', "/products/{$product->getId()}/edit?uniqid=form", [
            'form' => [
                'name' => 'Test product',
                'weight' => '10 g',
                'category' => $category2->getId(),
                'description' => 'Test descr',
            ],
        ]);

        $product = $productRepository->findOneBy(['id' => $product->getId()]);

        self::assertEquals('Test product', $product->getName());
        self::assertEquals('10 g', (string) $product->getWeight());
        self::assertEquals($category2->getId(), $product->getCategory()->getId());
        self::assertEquals('Test descr', $product->getDescription());
    }

    public function testEditPostWeightFormatFailure(): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);
        $category2 = $productCategoryRepository->findOneBy(['name' => 'Category 2']);

        $productRepository = $client->getContainer()->get(ProductRepository::class);
        $product = $productRepository->findOneBy(['name' => 'Product 1']);

        $client->request('POST', "/products/{$product->getId()}/edit?uniqid=form", [
            'form' => [
                'name' => 'Test product',
                'weight' => '10 gg',
                'category' => $category2->getId(),
                'description' => 'Test descr',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'An error has occurred during update of item "Test product".');
    }

    public function testCreateGet(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', "/products/create");

        self::assertResponseIsSuccessful();

        $this->assertFormFields($crawler, [
            'Name',
            'Weight',
            'Category',
            'Description',
        ]);
    }

    public function testCreatePost(): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);
        $category2 = $productCategoryRepository->findOneBy(['name' => 'Category 2']);

        $productRepository = $client->getContainer()->get(ProductRepository::class);

        $client->request('POST', "/products/create?uniqid=form", [
            'form' => [
                'name' => 'Test product',
                'weight' => '10 g',
                'category' => $category2->getId(),
                'description' => 'Test descr',
            ],
        ]);

        $product = $productRepository->findOneBy(['name' => 'Test product']);
        self::assertNotEmpty($product);

        self::assertEquals('Test product', $product->getName());
        self::assertEquals('10 g', (string) $product->getWeight());
        self::assertEquals($category2->getId(), $product->getCategory()->getId());
        self::assertEquals('Test descr', $product->getDescription());
    }

    public function testListEmpty(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/products');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.sonata-ba-list'));
    }

    public function testList(): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $crawler = $client->request('GET', '/products');

        self::assertResponseIsSuccessful();
        $this->assertTableHeaders($crawler, [
            '',
            'Name',
            'Weight',
            'Category',
            'Actions',
        ]);
        $this->assertTableRows($crawler, [
            ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
            ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
        ]);
    }

    /**
     * @dataProvider listFilterDataProvider
     */
    public function testListFilter(array $expectedRows, string $field, string $value): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $crawler = $client->request('GET', '/products', [
            'filter' => [
                $field => [
                    'value' => $value,
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();

        $this->assertTableRows($crawler, $expectedRows);
    }

    public function listFilterDataProvider(): Generator
    {
        yield 'name' => [
            [
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
            ],
            'name',
            'Product 2',
        ];

        yield 'weight_from' => [
            [
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
            ],
            'weight_from',
            '500 g',
        ];

        yield 'weight_from not applies' => [
            [
                ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
            ],
            'weight_from',
            '500 gg',
        ];

        yield 'weight_to' => [
            [
                ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
            ],
            'weight_to',
            '500 g',
        ];

        yield 'weight_to not applies' => [
            [
                ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
            ],
            'weight_to',
            '500 gg',
        ];

        yield 'category__name' => [
            [
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
            ],
            'category__name',
            'Category 2',
        ];
    }

    /**
     * @dataProvider listSortingDataProvider
     */
    public function testListSorting(array $expectedRows, string $field, string $order): void
    {
        $client = static::createClient();

        $this->loadDefaultProducts($client);

        $crawler = $client->request('GET', '/products', [
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
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
                ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
            ],
            'name',
            'DESC',
        ];

        yield [
            [
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
                ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
            ],
            'weight',
            'DESC',
        ];

        yield [
            [
                ['', 'Product 2', '2 kg', 'Category 2', 'Delete'],
                ['', 'Product 1', '100 g', 'Category 1', 'Delete'],
            ],
            'category',
            'DESC',
        ];
    }

    private function loadDefaultProducts(KernelBrowser $client): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $category1 = new ProductCategory();
        $category1->setName('Category 1');
        $em->persist($category1);

        $category2 = new ProductCategory();
        $category2->setName('Category 2');
        $em->persist($category2);

        $product = new Product();
        $product->setName('Product 1');
        $product->setWeight(new Weight(100, WeightUnit::g));
        $product->setCategory($category1);
        $product->setDescription('Product 1 desc');
        $em->persist($product);

        $product = new Product();
        $product->setName('Product 2');
        $product->setWeight(new Weight(2, WeightUnit::kg));
        $product->setCategory($category2);
        $product->setDescription('Product 2 desc');
        $em->persist($product);

        $em->flush();
    }
}
