<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/*
user_id,name,photo_url,username,twitter,instagram,num_followers,num_following,time_created,invited_by_user_profile
4,Rohan Seth,https://clubhouseprod.s3.amazonaws.com:443/4_b471abef-7c14-43af-999a-6ecd1dd1709c,rohan,rohanseth,null,4187268,599,2020-03-17T07:51:28.085566+00:00,null
5,Paul Davison,https://clubhouseprod.s3.amazonaws.com:443/5_e00ae119-7179-41aa-8808-2c96836d58c3,paul,pdavison,null,3718334,1861,2020-03-17T14:36:19.468976+00:00,null
8,Johnny Appleseed,,apple1,null,srt_tester_9,20,81,2020-03-19T19:47:00.323603+00:00,null
10,DK 🖍,https://clubhouseprod.s3.amazonaws.com:443/10_dd2a8509-8911-493e-8323-78e878f34a6c,dk,dksf,null,49538,173,2020-03-19T23:38:52.574777+00:00,null
12,Jonathan Gheller,https://clubhouseprod.s3.amazonaws.com:443/12_d70d175f-a12f-45b3-a56c-f6fa4b930f61,jonathan,jgheller,null,21250,81,2020-03-20T02:30:22.188084+00:00,null
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
