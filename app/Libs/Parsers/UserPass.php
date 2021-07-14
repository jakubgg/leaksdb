<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * Parser for generic user:pass dumps.
 */
class UserPass extends Parser
{

    /**
     * {@inheritdoc}
     */
    protected array $extensions = ['txt','csv',''];

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        preg_match('/^(.*?)[:|;|\|](.*?)$/', $line, $matches, PREG_OFFSET_CAPTURE);

        if (!isset($matches[1][0]) || !isset($matches[2][0])) {
            return false;
        }

        $data = [
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
