<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * MySpace 2008
 * 
 * Description:
 *  In approximately 2008, MySpace suffered a data breach that exposed almost 
 *  360 million accounts. In May 2016 the data was offered up for sale on the 
 *  "Real Deal" dark market website and included email addresses, usernames and 
 *  SHA1 hashes of the first 10 characters of the password converted to lowercase 
 *  and stored without a salt. The exact breach date is unknown, but analysis of 
 *  the data suggests it was 8 years before being made public.
 * 
 * Records: 
 *  - Official: 360 M (359,420,698)
 * 
 * Data: 
 *  - Email addresses, Passwords, Usernames
 * 
 * Formats: 
 *  - id:email:username:hash(sha1, 10 chars truncated):hash_2
 * 
 * References: 
 *  - https://www.troyhunt.com/dating-the-ginormous-myspace-breach/
 */
class MySpace extends Parser
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
        $line = trim($line);
        $parts = str_getcsv($line, $this->separator, "'");

        $map = [
            1 => 'email',
            2 => 'username',
            3 => 'hash',
            4 => 'hash_2', // ? not sure what that is
        ];

        return $this->parse($map, $parts);
    }
}
