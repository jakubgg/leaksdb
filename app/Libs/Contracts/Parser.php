<?php

namespace App\Libs\Contracts;

interface Parser
{
    /**
     * Extract data from a document line.
     *
     * @param  string $line
     * @return false|array
     */
    public function processLine(string $line);
}
