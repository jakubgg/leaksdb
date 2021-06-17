<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Ledger 2020 leak
 * 
 * Records: 1 M + 9.532
 * 
 * Formats: 
 *  - email
 *  - email| Name| Address | Address | Country| Phone
 * 
 * References: 
 *  - https://www.ledger.com/addressing-the-july-2020-e-commerce-and-marketing-data-breach
 *  - https://www.ledger.com/message-ledgers-ceo-data-leak
 */
class Ledger extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['txt'];

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $line = trim($line);
        if (strstr($line, '|')) {
            return $this->processOrder($line);
        } else {
            return $this->processSubscription($line);
        }
    }

    private function processSubscription($line)
    {
        return [
            'email' => $line,
        ];
    }

    private function processOrder($line)
    {
        $parts = explode('| ', $line);

        return [
            'email' => $parts[0],
            'name' => $parts[1],
            'address' => $parts[2] . ' ' . $parts[3],
            'country' => $parts[4],
            'phone' => $parts[5],
        ];
    }
}
