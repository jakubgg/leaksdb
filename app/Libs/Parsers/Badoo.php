<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Badoo 2013
 * 
 * Description:
 *  In June 2016, a data breach allegedly originating from the social website Badoo was 
 *  found to be circulating amongst traders. Likely obtained several years earlier, 
 *  the data contained 112 million unique email addresses with personal data including names, 
 *  birthdates and passwords stored as MD5 hashes. Whilst there are many indicators suggesting 
 *  Badoo did indeed suffer a data breach, the legitimacy of the data could not be emphatically 
 *  proven so this breach has been categorised as "unverified".
 * 
 * Records: 
 *  - Official: 112 M (112,005,531)
 * 
 * Data:
 *  - Dates of birth, Email addresses, Genders, Names, Passwords, Usernames
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
