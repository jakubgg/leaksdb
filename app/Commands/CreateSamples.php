<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class CreateSamples extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'create-samples {input} {output}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create sample files from a dump';

    /**
     * Total lines to write.
     */
    const LINES = 100;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $input = $this->argument('input');
        $output = $this->argument('output');

        $di = new \RecursiveDirectoryIterator($input);
        foreach (new \RecursiveIteratorIterator($di) as $filePath => $file) {
            if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                continue;
            }
            if (!$file->isFile()) {
                continue;
            }
            if (!in_array($file->getExtension(), ['txt', 'csv'])) {
                continue;
            }

            $outputFile = str_replace($input, $output, $filePath);
            $outputDir = dirname($outputFile);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $lines = '';
            $handle = fopen($filePath, 'r');
            for ($i = 0; $i < self::LINES; $i++) {
                $lines .= fgets($handle);
            }
            fclose($handle);

            file_put_contents($outputFile, $lines);
        }
    }
}
