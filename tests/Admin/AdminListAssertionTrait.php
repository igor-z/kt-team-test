<?php

namespace App\Tests\Admin;

use Symfony\Component\DomCrawler\Crawler;

trait AdminListAssertionTrait
{
    abstract public static function assertEquals($expected, $actual, string $message = ''): void;

    private function assertTableRows(Crawler $crawler, array $expectedRows): void
    {
        $actualRows = $crawler->filter('.sonata-ba-list tbody tr')->each(function(Crawler $rowNode) {
            return $rowNode->filter('td')->each(function (Crawler $cellNode) {
                return $cellNode->text();
            });
        });

        self::assertEquals($expectedRows, $actualRows);
    }

    private function assertTableHeaders(Crawler $crawler, array $headers): void
    {
        $actualHeaders = $crawler->filter('.sonata-ba-list thead th')->each(function (Crawler $headerNode, $i) {
            return $headerNode->text();
        });

        self::assertEquals($headers, $actualHeaders);
    }
}