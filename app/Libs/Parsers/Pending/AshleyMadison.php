<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * Ashley Madison 2015
 * 
 * Description: 
 *  In July 2015, the infidelity website Ashley Madison suffered a serious data breach. 
 *  The attackers threatened Ashley Madison with the full disclosure of the breach unless 
 *  the service was shut down. One month later, the database was dumped including more 
 *  than 30M unique email addresses. This breach has been classed as "sensitive" and is 
 *  not publicly searchable, although individuals may discover if they've been impacted by 
 *  registering for notifications. Read about this approach in detail.
 * 
 * Records: 
 *  - Official: 30 M (30,811,934) 
 *  - CSV: 9.693.860
 *  - SQL: 
 * 
 * Data:
 *  - Dates of birth, Email addresses, Ethnicities, Genders, Names, Passwords, Payment histories, 
 *    Phone numbers, Physical addresses, Security questions and answers, Sexual orientations, 
 *    Usernames, Website activity
 * 
 * Formats:
 *  - CSV
 *  - SQL
 * 
 * References:
 *  - https://www.troyhunt.com/heres-how-im-going-to-handle-ashley/
 *  - https://haveibeenpwned.com/PwnedWebsites#AshleyMadison
 */
class AshleyMAdison extends Parser
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['csv'];

    /**
     * {@inheritdoc }
     */
    protected $separator = ':';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        // TODO
    }
}
