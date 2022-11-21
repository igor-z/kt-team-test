<?php

declare(strict_types=1);

namespace App\Tests\Admin;

use App\Entity\Weight;
use App\Enum\WeightUnit;
use App\Kernel;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Tests\SessionAwareTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ProductControllerTest extends WebTestCase
{
    use SessionAwareTrait;

    public function testUploadImportFileActionOverwrite(): void
    {
        $fileName = 'test.xml';

        $client = static::createClient();

        $session = $this->startSession($client);

        $projectDir = $this->getProjectDir($client);

        $file = $this->getImportFile($projectDir, $session, $fileName);

        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => $fileName]),
            server: [
                'HTTP_CONTENT_RANGE' => 'bytes 0-4/10',
            ],
            content: 'test',
        );

        self::assertResponseIsSuccessful();
        $this->assertFileContains('test', $file);

        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => $fileName]),
            server: [
                'HTTP_CONTENT_RANGE' => 'bytes 0-5/10',
            ],
            content: '12345',
        );

        self::assertResponseIsSuccessful();
        $this->assertFileContains('12345', $file);
    }

    public function testUploadImportFileActionAppend(): void
    {
        $fileName = 'test.xml';

        $client = static::createClient();

        $session = $this->startSession($client);

        $file = $this->getImportFile($this->getProjectDir($client), $session, $fileName);

        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => $fileName]),
            server: [
                'HTTP_CONTENT_RANGE' => 'bytes 0-5/10',
            ],
            content: '12345',
        );

        self::assertResponseIsSuccessful();
        $this->assertFileContains('12345', $file);

        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => $fileName]),
            server: [
                'HTTP_CONTENT_RANGE' => 'bytes 5-10/10',
            ],
            content: '67890',
        );

        self::assertResponseIsSuccessful();
        $this->assertFileContains('1234567890', $file);
    }

    public function testUploadImportFileActionWrongContentRangeFormat(): void
    {
        $client = static::createClient();

        $this->startSession($client);

        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => 'test.xml']),
            server: [
                'HTTP_CONTENT_RANGE' => 'bytes d1-5/10',
            ],
            content: '12345',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUploadImportFileActionWrongContentRangeUnit(): void
    {
        $client = static::createClient();

        $this->startSession($client);

        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => 'test.xml']),
            server: [
                'HTTP_CONTENT_RANGE' => 'megabytes 0-5/10',
            ],
            content: '12345',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testImportActionGet(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/products/import'
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('section.content h4', 'Products import');
        self::assertSelectorExists('section.content input[type=file]');
        self::assertSelectorTextContains('section.content button[id=button_import]', 'Import');
    }

    public function testImportAction(): void
    {
        $filename = 'test.xml';

        $fileContent = <<<XML
        <?xml version="1.0"?>
        <products>
            <product>
                <name>Product 1</name>
                <description>Description 1</description>
                <weight>30 g</weight>
                <category>Category 1</category>
            </product>
            <product>
                <name>Product 2</name>
                <description>Description 2</description>
                <weight>10 kg</weight>
                <category>Category 1</category>
            </product>
            <product>
                <name>Product 3</name>
                <description>Description 2</description>
                <weight>10 kg</weight>
                <category>Category 2</category>
            </product>
        </products>
        XML;

        $client = static::createClient();

        $session = $this->startSession($client);

        $this->uploadFile($client, $filename, $fileContent);

        $client->request('POST', '/products/import', [
            'filename' => $filename,
        ]);

        self::assertResponseIsSuccessful();
        self::assertFileDoesNotExist($this->getImportFile($this->getProjectDir($client), $session, $filename));
        $this->assertProductsExist($client, [
            [
                'name' => 'Product 1',
                'description' => 'Description 1',
                'weight' => new Weight(30, WeightUnit::g),
                'category' => 'Category 1',
            ],
            [
                'name' => 'Product 2',
                'description' => 'Description 2',
                'weight' => new Weight(10, WeightUnit::kg),
                'category' => 'Category 1',
            ],
            [
                'name' => 'Product 3',
                'description' => 'Description 2',
                'weight' => new Weight(10, WeightUnit::kg),
                'category' => 'Category 2',
            ],
        ]);
        $this->assertProductCategoriesExist($client, [
            [
                'name' => 'Category 1',
            ],
            [
                'name' => 'Category 2',
            ],
        ]);
    }

    /**
     * @param array<array{
     *     name: string,
     *     description: string,
     *     weight: Weight,
     *     category: string,
     * }> $expectedProducts
     */
    private function assertProductsExist(KernelBrowser $client, array $expectedProducts): void
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $client->getContainer()->get(ProductRepository::class);

        foreach ($expectedProducts as $expectedProduct) {
            $product = $productRepository->findOneBy([
                'name' => $expectedProduct['name'],
            ]);

            self::assertNotEmpty($product, "Product with name \"{$expectedProduct['name']}\" must exist");
            self::assertEquals($expectedProduct, [
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'weight' => $product->getWeight(),
                'category' => $product->getCategory()->getName(),
            ]);
        }
    }

    /**
     * @param array<array{
     *     name: string,
     * }> $expectedProductCategories
     */
    private function assertProductCategoriesExist(KernelBrowser $client, array $expectedProductCategories): void
    {
        /** @var ProductCategoryRepository $productCategoryRepository */
        $productCategoryRepository = $client->getContainer()->get(ProductCategoryRepository::class);

        foreach ($expectedProductCategories as $expectedProductCategory) {
            $productCategory = $productCategoryRepository->findOneBy([
                'name' => $expectedProductCategory['name'],
            ]);

            self::assertNotEmpty($productCategory, "ProductCategory with name \"{$expectedProductCategory['name']}\" must exist");
        }
    }

    private function assertFileContains(string $expectedContent, string $file): void
    {
        self::assertFileExists($file);
        self::assertEquals($expectedContent, file_get_contents($file));
    }

    private function getImportFile(string $projectDir, Session $session, string $fileName): string
    {
        return "$projectDir/var/uploaded/{$session->getId()}/" . sha1($fileName);
    }

    private function getProjectDir(KernelBrowser $client): string
    {
        return $client->getContainer()->get(Kernel::class)->getProjectDir();
    }

    private function uploadFile(KernelBrowser $client, string $fileName, string $content): void
    {
        $client->request(
            'POST',
            '/products/upload-import-file?'.http_build_query(['filename' => $fileName]),
            server: [
                'HTTP_CONTENT_RANGE' => 'bytes 0-' . mb_strlen($content) . '/' . mb_strlen($content),
            ],
            content: $content,
        );
    }
}