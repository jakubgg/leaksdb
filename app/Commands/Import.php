<?php

namespace App\Commands;

use App\Libs\Contracts\Interfaces\Parser;
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->client = ClientBuilder::create()->setHosts([self::HOST])->build();

        $path = $this->argument('path');
        $this->name = $this->argument('name');
        $parserClassName = 'App\\Libs\\Parsers\\' . $this->argument('parser');

        $di = new \RecursiveDirectoryIterator($path);
        foreach (new \RecursiveIteratorIterator($di) as $filePath => $file) {
            if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                continue;
            }

            $parser = new $parserClassName($filePath);

            if ($parser->canProcessFile()) {
                $this->processFile($parser);
            } else {
                $this->error('File ignored: ' . $filePath);
            }
        }
    }

    private function processFile(Parser $parser)
    {
        $filePath = $parser->getFilePath();
        $this->info('Reading ' . $filePath);

        if (File::where('path', $filePath)->exists()) {
            $this->line('File already processed!');
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error('Cannot open the file!');
            return;
        }

        $lines = $parser->countLines();
        $this->comment('File contains ' . number_format($lines, 0, '', '.') . ' records.');
        $bar = $this->output->createProgressBar($lines);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $bar->start();

        $total = 0;
        $data = ['body' => []];
        while (!feof($handle)) {

            $bar->advance();

            $line = fgets($handle);
            if (!$line) {
                continue;
            }

            $processedLine = $parser->processLine($line);
            if (!$processedLine) {
                $this->line('');
                $this->error('Line not processed');
                echo $line . PHP_EOL;
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
        }
        fclose($handle);

        // Sent last data (< chunk)
        $this->insert($data);

        $bar->finish();
        $this->line('');

        $this->comment('Total non-processed lines: ' . ($lines - $total));

        File::create(['path' => $filePath]);
    }

    private function insert($data)
    {
        do {
            $res = $this->client->bulk($data);
            if ($res['errors']) {
                $this->error('ES Error');
                print_r($res);
            } else {
                break;
            }
        } while (true);
    }
}
