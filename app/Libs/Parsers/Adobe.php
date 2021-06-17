<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Adobe 2013
 * 
 * Description: 
 *  In October 2013, 153 million Adobe accounts were breached with each containing 
 *  an internal ID, username, email, encrypted password and a password hint in 
 *  plain text. The password cryptography was poorly done and many were quickly 
 *  resolved back to plain text. The unencrypted hints also disclosed much about 
 *  the passwords adding further to the risk that hundreds of millions of Adobe 
 *  customers already faced.
 * 
 * Records: 
 *  - Official: 152 M (152,445,165)
 * 
 * Data: 
 *  - Email addresses, Password hints, Passwords, Usernames
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
