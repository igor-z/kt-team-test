<?php

namespace App\Tests\Admin;
use Symfony\Component\DomCrawler\Crawler;

trait AdminFormAssertionTrait
{
    abstract public static function assertEquals($expected, $actual, string $message = ''): void;

    private function assertFormFields(Crawler $crawler, array $expectedFields): void
    {
        $actualFields = $crawler->filter('section.content form .control-label')->each(function (Crawler $crawler) {
            return $crawler->text();
        });

        self::assertEquals($expectedFields, $actualFields);
    }
}