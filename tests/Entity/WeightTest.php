<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Weight;
use App\Enum\WeightUnit;
use DomainException;
use Generator;
use PHPUnit\Framework\TestCase;
use ValueError;

class WeightTest extends TestCase
{
    /**
     * @dataProvider fromStringDataProvider
     */
    public function testFromString(int $expectedGrams, string $expectedWeightString, WeightUnit $expectedUnit, string $weightString): void
    {
        $weight = Weight::fromString($weightString);
        self::assertEquals($expectedGrams, $weight->getGrams());
        self::assertEquals($expectedWeightString, (string) $weight);
        self::assertEquals($expectedUnit, $weight->getUnit());
    }

    public function testFromStringWrongFormat(): void
    {
        $this->expectException(DomainException::class);

        Weight::fromString('g100');
    }

    public function testFromStringWrongUnit(): void
    {
        $this->expectException(ValueError::class);

        Weight::fromString('100 mg');
    }

    public function fromStringDataProvider(): Generator
    {
        yield [
            100,
            '100 g',
            WeightUnit::g,
            '100   g'
        ];

        yield [
            10000,
            '10 kg',
            WeightUnit::kg,
            '10kg'
        ];
    }
}