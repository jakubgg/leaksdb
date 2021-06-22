<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * Dropbox 2012
 * 
 * Description:
 *  In mid-2012, Dropbox suffered a data breach which exposed the stored 
 *  credentials of tens of millions of their customers. In August 2016, 
 *  they forced password resets for customers they believed may be at risk. 
 *  A large volume of data totalling over 68 million records was subsequently 
 *  traded online and included email addresses and salted hashes of passwords 
 *  (half of them SHA1, half of them bcrypt).
 * 
 * Records: 
 *  - Official: 68 M (68,648,009)
 * 
 * Data:
 *  - Email addresses, Passwords
 * 
 * Formats: 
 *  - email:hash(md5|sha1|bcrypt)
 * 
 * References: 
 *  - https://www.troyhunt.com/the-dropbox-hack-is-real/
 *  - https://www.vice.com/en/article/nz74qb/hackers-stole-over-60-million-dropbox-accounts
 */
class Dropbox extends Parser
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
