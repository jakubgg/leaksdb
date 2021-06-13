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
    protected $signature = 'import {name} {path}';

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
    const INDEX = 'leaks_test';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->client = ClientBuilder::create()->setHosts([self::HOST])->build();

        $path = $this->argument('path');
        $this->name = $this->argument('name');

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
                $res = $this->processLine(fgets($handle));
                if ($res) {
                    $data['body'][] = [
                        'index' => [
                            '_index' => self::INDEX,
                            '_id' => md5(json_encode($data)),
                        ]
                    ];
                    // $res['file'] = $filePath;
                    $data['body'][] = $res;
                }

                if (count($data['body']) >= self::CHUNK) {
                    $this->insert($data);
                    $data = ['body' => []];
                }
            }
            fclose($handle);
        }

        File::create(['path' => $filePath]);
    }

    private function processLine($line)
    {
        if (!$line) {
            return;
        }

        preg_match('/^(.*?)[:|;|\|](.*?)$/', $line, $matches, PREG_OFFSET_CAPTURE);

        if (!isset($matches[1][0]) || !isset($matches[2][0])) {
            $this->error('Line not match: ' . $line);
            return;
        }

        $data = [
            'dump' => $this->name,
            'password' => $matches[2][0],
        ];

        if (filter_var($matches[1][0], FILTER_VALIDATE_EMAIL)) {
            $data['email'] = $matches[1][0];
        } else {
            $data['user'] = $matches[1][0];
        }

        // $this->line($data['user'] . ' : ' . $data['password']);

        return $data;
    }

    private function insert($data)
    {
        // $this->info('Inserting bulk');
        $res = $this->client->bulk($data);
        if ($res['errors']) {
            $this->error($res['errors']);
            exit;
        }
    }
}
