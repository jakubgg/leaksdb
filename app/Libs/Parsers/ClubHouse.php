<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * ClubHouse 2021
 * 
 * Description:
 *  Clubhouse data leak: 1.3 million scraped user records leaked online for free
 * 
 * Records: 
 *  - Official: 1.3 M (1,300,517)
 * 
 * Formats: 
 *  - user_id,name,photo_url,username,twitter,instagram,num_followers,num_following,time_created,invited_by_user_profile
 * 
 * References: 
 *  - https://www.businessinsider.com/clubhouse-data-leak-1-million-users-2021-4
 * 
 * Download:
 *  - http://3kp6j22pz3zkv76yutctosa6djpj4yib2icvdqxucdaxxedumhqicpad.onion/clubhouse/
 *  - http://fayloobmennik.cloud/7419760
 *  - https://anonfiles.com/tfL362q0u3/user_rar
 */
class ClubHouse extends Parser
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['csv'];

    /**
     * {@inheritdoc }
     */
    protected $separator = ',';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $line = $this->cleanLine($line);
        $parts = str_getcsv($line);

        $map = [
            0 => 'clubhouse_id',
            1 => 'name',
            2 => 'photo',
            3 => 'username',
            4 => 'twitter',
            5 => 'instagram',
            6 => 'followers',
            7 => 'following',
            9 => 'invited_by',
        ];

        $data = $this->parse($map, $parts);

        $data['created_at'] = date('Y-m-d', strtotime($parts[8]));

        return $data;
    }
}
