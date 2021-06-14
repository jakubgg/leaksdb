<?php

namespace App\Commands;

use App\Models\File;
use LaravelZero\Framework\Commands\Command;
use Elasticsearch\ClientBuilder;

class Import extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import {name} {path} {parser}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Import dumps from a directory';

    /**
     * ES Client
     *
     * @var Elasticsearch\ClientBuilder
     */
    protected $client;

    /**
     * ES Host.
     */
    const HOST = 'http://192.168.1.10:9200';

    /**
     * ES Index
     */
    const INDEX = 'leaks';

    /**
     * Chunk size
     */
    const CHUNK = 500;

    /**
     * Dump name
     *
     * @var string
     */
    protected $name;

    /**
     * Parser class
     *
     * @var App\Libs\Contracts\Parser
     */
    protected $parser;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->client = ClientBuilder::create()->setHosts([self::HOST])->build();

        $path = $this->argument('path');
        $this->name = $this->argument('name');
        $parserClass = 'App\\Libs\\Parsers\\' . $this->argument('parser');
        $this->parser = new $parserClass;

        $this->info('Analysing ' . $path);

        $di = new \RecursiveDirectoryIterator($path);
        foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
            if ($file->getExtension() == 'txt' || $file->getExtension() == 'csv') {
                $this->processFile($filename);
            }
        }
    }

    private function processFile($filePath)
    {
        $this->info('Reading ' . $filePath);

        if (File::where('path', $filePath)->exists()) {
            $this->line('Path already processed!');
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error('Cannot open the file!');
            return;
        }

        $lines = $this->countLines($filePath);
        $this->comment('File contains ' . $lines . ' records.');
        $bar = $this->output->createProgressBar($lines);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $bar->start();

        $total = 0;
        $data = ['body' => []];
        while (!feof($handle)) {

            $line = fgets($handle);
            if (!$line) {
                continue;
            }

            $processedLine = $this->parser->processLine($line);
            if (!$processedLine) {
                $this->error('Line not processed' . $line);
                continue;
            }

            // Add the dump name
            $processedLine['dump'] = $this->name;

            // Prepare the ES request
            $data['body'][] = [
                'index' => [
                    '_index' => self::INDEX,
                    '_id' => md5(json_encode($processedLine)),
                ]
            ];
            $data['body'][] = $processedLine;

            $total++;
            if ($total % self::CHUNK == 0) {
                $this->insert($data);
                $data['body'] = [];
            }

            $bar->advance();
        }
        fclose($handle);

        // Sent last data (< chunk)
        $this->insert($data);

        $bar->finish();

        File::create(['path' => $filePath]);
    }

    private function insert($data)
    {
        do {
            $res = $this->client->bulk($data);
            if ($res['errors']) {
                $this->error($res['errors']);
            } else {
                break;
            }
        } while (true);
    }

    /**
     * Count the lines of a file.
     *
     * @param string $filePath
     * @return int
     */
    private function countLines(string $filePath)
    {
        $handle = fopen($filePath, 'r');
        $count = 0;
        while (fgets($handle)) {
            $count++;
        }
        fclose($handle);
        return $count;
    }
}
