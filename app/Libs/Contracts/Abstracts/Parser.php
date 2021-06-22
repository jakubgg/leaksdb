<?php

namespace App\Libs\Contracts\Abstracts;

use App\Libs\Contracts\Interfaces\Parser as InterfacesParser;

abstract class Parser implements InterfacesParser
{
    /**
     * File Path
     *
     * @var string
     */
    protected $filePath;

    /**
     * Fields separator
     *
     * @var string
     */
    protected $separator;

    /**
     * Allowed extensions.
     *
     * @var array
     */
    protected $extensions = ['txt', 'csv'];

    /**
     * Initializator.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * @param Command $command
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->init();
    }

    /**
     * Clean the line.
     *
     * @param string $line
     * @return string
     */
    public function cleanLine(string $line)
    {
        $line = mb_convert_encoding($line, "UTF-8");
        // $line = utf8_decode($line);
        $line = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $line);
        // $line = utf8_encode($line);

        return $line;
    }

    /**
     * Get file path.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * {@inheritdoc }
     */
    public function countLines()
    {
        $handle = fopen($this->filePath, 'r');
        $count = 0;
        while (fgets($handle)) {
            $count++;
        }
        fclose($handle);
        return $count;
    }

    /**
     * {@inheritdoc }
     */
    public function canProcessFile()
    {
        $ext = pathinfo($this->filePath, PATHINFO_EXTENSION);

        return in_array($ext, $this->extensions);
    }

    /**
     * Parse a map.
     *
     * @param array $map
     * @param array $parts
     * @return array
     */
    protected function parse($map, $parts)
    {
        $data = [];
        foreach ($map as $key => $field) {
            if (isset($parts[$key]) && $parts[$key] && $parts[$key] !== 'null') {
                $data[$field] = $parts[$key];
            }
        }
        return $data;
    }
}
