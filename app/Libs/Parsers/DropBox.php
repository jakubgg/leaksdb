<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * DropBox 2012 leak
 * 
 * Records: 68 M
 * 
 * Formats: 
 *  - email:hash(md5|sha1|bcrypt)
 * 
 * References: 
 *  - https://www.troyhunt.com/the-dropbox-hack-is-real/
 *  - https://www.vice.com/en/article/nz74qb/hackers-stole-over-60-million-dropbox-accounts
 */
class DropBox extends Parser implements ParserInterface
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

        $parts = array_map('trim', $parts);

        return [
            'email' => $parts[0],
            'hash' => $parts[1],
            // 'hash_type' => $this->getHashType($parts[1]),
        ];
    }

    private function getHashType($hash)
    {
        if (substr($hash, 0, 1) == '$') {
            return 'bcrypt';
        } elseif (strlen($hash) == 40) {
            return 'sha1';
        } else {
            return 'md5';
        }
    }
}
