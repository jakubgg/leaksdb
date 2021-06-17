<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * LinkedIn 2012
 * 
 * Description:
 *  In May 2016, LinkedIn had 164 million email addresses and passwords exposed. 
 *  Originally hacked in 2012, the data remained out of sight until being offered 
 *  for sale on a dark market site 4 years later. The passwords in the breach were 
 *  stored as SHA1 hashes without salt, the vast majority of which were quickly 
 *  cracked in the days following the release of the data.
 * 
 * Records: 
 *  - Official: 167 M (164,611,595)
 * 
 * Data: 
 *  - Email addresses, Passwords
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
