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

        $map = [
            0 => 'adobe_id',
            2 => 'email',
            3 => 'hash',
            4 => 'secret_question',
        ];

        return $this->parse($map, $parts);
    }
}
