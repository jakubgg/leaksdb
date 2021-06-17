<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * USA 280M
 * 
 * Description:
 *  Data leaked: Full names, Home Addresses, Income/Salary, House cost, Amount of children, 
 *  Phone Numbers, Email Addresses (Some people have multiple linked), Amount of pets, and a lot 
 *  of other data. Look at the CSV headers. Note: There are no passwords in this leak.
 * 
 * Records: 
 *  - Official: 280M (250.808.966)
 * 
 * Formats: 
 * 
 * Download: 
 *  - http://3kp6j22pz3zkv76yutctosa6djpj4yib2icvdqxucdaxxedumhqicpad.onion/usa-280m/
 */
class USA280 extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['txt'];

    /**
     * {@inheritdoc }
     */
    protected $separator = ':';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        // TODO
    }
}
