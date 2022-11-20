<?php

declare(strict_types=1);

namespace App\Import;

use App\Entity\Weight;
use SimpleXMLElement;
use XMLReader;

class XMLDataReader
{
    /**
     * @return iterable<ImportRow>
     */
    public function read(string $fileName): iterable
    {
        /** @var XMLReader|false $reader */
        $reader = XMLReader::open('file://'.$fileName);

        while ($reader->read()) {
            if ($reader->name !== 'product' || $reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $element = new SimpleXMLElement($reader->readOuterXML());

            yield new ImportRow(
                (string) $element->name,
                (string) $element->description,
                Weight::fromString((string) $element->weight),
                (string) $element->category,
            );
        }
    }
}