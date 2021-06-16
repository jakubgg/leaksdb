<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

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

        return [
            'adobe_id' => $parts[0],
            'email' => $parts[2],
            'hash' => $parts[3],
            'secret_question' => $parts[4],
        ];
    }
}
