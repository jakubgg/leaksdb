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
     * File map for avoid re-counting all the files.
     *
     * @var array
     */
    protected $fileMap = [];

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
        $this->analyse($path);

        $di = new \RecursiveDirectoryIterator($path);
        foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
            if ($file->getExtension() == 'txt' || $file->getExtension() == 'csv') {
                $this->processFile($filename);
            } else {
                $this->error('File ignored: ' . $filename);
            }
        }
    }

    private function processFile($filePath)
    {
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

        $lines = $this->fileMap[$filePath];
        $this->comment('File contains ' . number_format($lines, 0, '', '.') . ' records.');
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

            $bar->advance();

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
                $this->error($res['errors']);
            } else {
                break;
            }
        } while (true);
    }

    /**
     * Analyse a path.
     *
     * @param string $path
     * @return void
     */
    private function analyse(string $path)
    {
        $nonProcessable = 0;
        $processable = 0;
        $totalLines = 0;

        $di = new \RecursiveDirectoryIterator($path);
        foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
            if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                continue;
            }
            if ($file->getExtension() == 'txt' || $file->getExtension() == 'csv') {
                $processable++;
                $lines = $this->countLines($filename);
                $totalLines += $lines;
                $this->fileMap[$filename] = $lines;
            } else {
                $nonProcessable++;
            }
        }

        $this->comment('Non-processable files: ' . number_format($nonProcessable, 0, '', '.'));
        $this->comment('Processable files: ' . number_format($processable, 0, '', '.'));
        $this->comment('Total Lines: ' . number_format($totalLines, 0, '', '.'));
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
