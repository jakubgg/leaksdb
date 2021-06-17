<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * LinkedIn 2012 leak
 * 
 * Records: 167 M
 * 
 * Formats: 
 *  - email:hash(sha1)
 *  - email:xxx
 * 
 * References: 
 *  - https://www.troyhunt.com/observations-and-thoughts-on-the-linkedin-data-breach/
 */
class LinkedIn extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['txt'];

    /**
     * {@inheritdoc }
     */
    protected $separator = [':'];

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $line = trim($line);
        $parts = explode($this->separator, $line);

        $data = [
            'email' => $parts[0],
        ];

        if ($parts[1] != 'xxx') {
            $data['hash'] = $parts[1];
        }

        return $data;
    }
}
