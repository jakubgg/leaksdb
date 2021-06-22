<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * 000webhost 2015
 * 
 * Description:
 *  In approximately March 2015, the free web hosting provider 000webhost 
 *  suffered a major data breach that exposed almost 15 million customer records. 
 *  The data was sold and traded before 000webhost was alerted in October. 
 *  The breach included names, email addresses and plain text passwords.
 * 
 * Records: 
 *  - Official: 15 M (14,936,670)
 * 
 * Data: 
 *  - Email addresses, IP addresses, Names, Passwords
 * 
 * Formats: 
 *  - name:email:ip:password
 * 
 * References: 
 *  - https://www.troyhunt.com/breaches-traders-plain-text-passwords/
 */
class Webhost000 extends Parser
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
        $line = $this->cleanLine($line);
        $line = trim($line);
        $parts = explode($this->separator, $line);

        $map = [
            0 => 'name',
            1 => 'email',
            2 => 'ip',
            3 => 'password',
        ];

        return $this->parse($map, $parts);
    }
}
