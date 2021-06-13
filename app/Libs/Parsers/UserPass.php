<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Parser;

/*
user:pass
email:pass
*/

class UserPass implements Parser
{
    public function processLine(string $line)
    {
        preg_match('/^(.*?)[:|;|\|](.*?)$/', $line, $matches, PREG_OFFSET_CAPTURE);

        if (!isset($matches[1][0]) || !isset($matches[2][0])) {
            return false;
        }

        $data = [
            'dump' => $this->name,
            'password' => $matches[2][0],
        ];

        if (filter_var($matches[1][0], FILTER_VALIDATE_EMAIL)) {
            $data['email'] = $matches[1][0];
        } else {
            $data['user'] = $matches[1][0];
        }

        return $data;
    }
}
