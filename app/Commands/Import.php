<?php

namespace App\Commands;

use App\Libs\Contracts\Interfaces\Parser;
use App\Models\File;
use LaravelZero\Framework\Commands\Command;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Storage;

class Import extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import {name} {path} {parser} {--delete} {--test}';

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
     * Test mode?
     *
     * @var bool
     */
    protected $test = false;

    /**
     * Non-processed lines output log.
     */
    const NONPROCESSED = 'non-processed.txt';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ES_URL')])->build();

        $path = $this->argument('path');
        $this->name = $this->argument('name');
        $this->test = $this->option('test');

        if ($this->option('delete')) {
            $this->delete();
        }

        Storage::delete(self::NONPROCESSED);

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
        $this->newLine();
        $this->info('Reading ' . $filePath);

        if (!$this->test && !$this->option('delete') && File::where('path', $filePath)->exists()) {
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
                Storage::append(self::NONPROCESSED, $line, null);
                continue;
            }

            // Add the leak name
            $processedLine['leak'] = $this->name;

            if ($this->test) {
                print_r($processedLine);
            }

            // Prepare the ES request
            $data['body'][] = [
                'index' => [
                    '_index' => env('ES_INDEX'),
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

        $bar->finish();
        $this->newLine();

        if ($total == 0) {
            $this->error('No lines were processed');
            exit;
        }
        $nonProcessed = $lines - $total;
        if ($nonProcessed) {
            $this->error('Non-processed lines: ' . $nonProcessed);
        }

        // Sent last data (< chunk)
        if (!empty($data['body'])) {
            $this->insert($data);
        }

        if (!$this->test) {
            File::create([
                'path' => $filePath,
                'lines' => $lines,
                'processed' => $total,
            ]);
        }
    }

    private function insert($data)
    {
        if ($this->test) {
            return;
        }

        do {
            $res = $this->client->bulk($data);
            if ($res['errors']) {
                $this->error('ES Error');
                // print_r($res);
            } else {
                break;
            }
        } while (true);
    }

    private function delete()
    {
        try {
            $this->client->delete([
                'index' => env('ES_INDEX'),
                'leak' => $this->name,
            ]);
        } catch (\Exception $e) {
            // 
        }
    }
}
