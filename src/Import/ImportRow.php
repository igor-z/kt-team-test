<?php

declare(strict_types=1);

namespace App\Import;

use App\Entity\Weight;

class ImportRow
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly Weight $weight,
        public readonly string $category,
    ) {
    }
}