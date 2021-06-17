<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Badoo 2013 leak
 * 
 * Records: 112 M
 * 
 * Formats: 
 *  - id:email:username:hash(md5):name:?:?:birthdate:gender:?:?:?
 * 
 * References: 
 *  - https://www.businessinsider.com/clubhouse-data-leak-1-million-users-2021-4
 *  - https://haveibeenpwned.com/PwnedWebsites#Badoo 
 */
class Badoo extends Parser implements ParserInterface
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
        $parts = explode($this->separator, $line);

        $map = [
            0 => 'badoo_id',
            1 => 'email',
            2 => 'username',
            3 => 'hash',
            4 => 'name',
            9 => 'gender',
        ];

        $data = $this->parse($map, $parts);

        if ($parts[7] != '0000-00-00') {
            $data['birthdate'] = $parts[7];
        }

        return $data;
    }
}
