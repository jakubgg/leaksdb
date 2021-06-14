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

        $total = 0;
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

                $total++;
                if ($total % self::CHUNK == 0) {
                    $this->insert($data);
                    $this->line($total . ' records sent');
                    $data['body'] = [];
                }
            }
            fclose($handle);
        }

        // Sent last data (< chunk)
        $this->insert($data);

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
}
