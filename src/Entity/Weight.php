<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WeightUnit;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use DomainException;
use RuntimeException;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[Embeddable]
class Weight
{
    #[Column(type: 'integer')]
    private int $grams;

    #[Column(type: 'string', length: 5, enumType: WeightUnit::class)]
    private WeightUnit $unit;

    public function __construct(int $value, WeightUnit $unit)
    {
        $this->grams = $value * $unit->grams();
        $this->unit = $unit;
    }

    public static function fromString(string $weight): self
    {
        if (preg_match('/^(\d+)(\w+)$/', str_replace(' ', '', $weight), $matches)) {
            [, $value, $unit] = $matches;
        } else {
            throw new DomainException(sprintf('Wrong weight format. Expected "2 kg", actual "%s"', $weight));
        }

        $unit = WeightUnit::from($unit);

        return new Weight((int) $value, $unit);
    }

    private function getValue(): int
    {
        return (int) round($this->grams / $this->unit->grams());
    }

    public function getGrams(): int
    {
        return $this->grams;
    }

    public function getUnit(): WeightUnit
    {
        return $this->unit;
    }

    public function __toString(): string
    {
        return $this->getValue() . ' ' . $this->unit->name;
    }
}