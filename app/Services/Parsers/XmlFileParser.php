<?php


namespace App\Services\Parsers;

use App\DTO\TransactionDTO;
use Generator;
use RuntimeException;
use XMLReader;
use SimpleXMLElement;

class XmlFileParser implements FileParserInterface
{
    /**
     * @return Generator<int, TransactionDTO>
     */
    public function parse(string $filePath): Generator
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException(
                "Cannot read XML file: {$filePath}"
            );
        }

        $reader = new XMLReader();
        if(!$reader->open($filePath)) {
            throw new RuntimeException("Cannot open XML file: {$filePath}");
        }

        try {
            while($reader->read()) {
                if($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'transaction') {
                    $nodeXml = $reader->readOuterXml();
                    if($nodeXml === '') {
                        continue;
                    }

                    $element = simplexml_load_string($nodeXml);
                    if($element === false) {
                        continue;
                    }

                    $data = [
                        'transaction_id' => isset($element->transaction_id) ? (string) $element->transaction_id : null,
                        'account_number' => isset($element->account_number) ? (string) $element->account_number : null,
                        'transaction_date' => isset($element->transaction_date) ? (string) $element->transaction_date : null,
                        'amount' => isset($element->amount) ? (string) $element->amount : null,
                        'currency' => isset($element->currency) ? (string) $element->currency : null,
                    ];

                    yield TransactionDTO::fromArray($data);

                }

            }
            
        } finally {
            $reader->close();
        }
    }
}