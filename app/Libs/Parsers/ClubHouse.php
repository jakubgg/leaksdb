<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * ClubHouse 2012 leak
 * 
 * Records: 1.3 M
 * 
 * Formats: 
 *  - user_id,name,photo_url,username,twitter,instagram,num_followers,num_following,time_created,invited_by_user_profile
 * 
 * References: 
 *  - https://www.businessinsider.com/clubhouse-data-leak-1-million-users-2021-4
 */
class ClubHouse extends Parser implements ParserInterface
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
