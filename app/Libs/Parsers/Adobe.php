<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Adobe 2013 leak
 * 
 * Records: 152 M
 * 
 * Formats: 
 *  - id-|-?-|-email-|-encrypted_password-|-pass_hint|--
 * 
 * References: 
 *  - https://haveibeenpwned.com/PwnedWebsites#Adobe
 *  - https://www.troyhunt.com/adobe-credentials-and-serious/
 */
class Adobe extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['txt'];

    /**
     * {@inheritdoc }
     */
    protected $separator = '-|-';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $line = $this->cleanLine($line);
        $line = substr($line, 0, -4);
        $parts = explode($this->separator, $line);

        $map = [
            0 => 'adobe_id',
            2 => 'email',
            3 => 'encrypted_password',
            4 => 'hint',
        ];

        return $this->parse($map, $parts);
    }
}
