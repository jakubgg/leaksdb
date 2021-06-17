<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Ledger 2020
 * 
 * Description:
 *  In June 2020, the hardware crypto wallet manufacturer Ledger suffered a 
 *  data breach that exposed over 1 million email addresses. The data was 
 *  initially sold before being dumped publicly in December 2020 and included 
 *  names, physical addresses and phone numbers. The data was provided to HIBP 
 *  by Alon Gal, CTO of cybercrime intelligence firm Hudson Rock.
 * 
 * Records: 
 *  - Official: 1 M (1,075,241)
 * 
 * Data:
 *  - Email addresses, Names, Phone numbers, Physical addresses
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
