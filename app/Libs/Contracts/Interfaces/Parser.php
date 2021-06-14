<?php

namespace App\Libs\Contracts\Interfaces;

interface Parser
{
    /**
     * Extract data from a document line.
     *
     * @param  string $line
     * @return false|array
     */
    public function processLine(string $line);

    /**
     * Get file path.
     *
     * @return string
     */
    public function getFilePath();

    /**
     * Count all the lines of the file.
     *
     * @return int
     */
    public function countLines();
}
