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
    const CHUNK = 1000;

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
            if ($file->getExtension() == 'txt') {
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
        if ($handle) {
            $data = ['body' => []];
            while (!feof($handle)) {

                $line = fgets($handle);
                if (!$line) {
                    continue;
                }

                $res = $this->parser->processLine($line);
                if (!$res) {
                    $this->error('Line not processed' . $line);
                    continue;
                }

                $res['dump'] = $this->name;

                $data['body'][] = [
                    'index' => [
                        '_index' => self::INDEX,
                        '_id' => md5(json_encode($data)),
                    ]
                ];
                $data['body'][] = $res;

                if (count($data['body']) >= self::CHUNK) {
                    $this->insert($data);
                    $data = ['body' => []];
                }
            }
            fclose($handle);
        }

        File::create(['path' => $filePath]);
    }

    private function insert($data)
    {
        $this->line('Sending ES Chunk');
        $res = $this->client->bulk($data);
        if ($res['errors']) {
            $this->error($res['errors']);
            exit;
        }
    }
}
