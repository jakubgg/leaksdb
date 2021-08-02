<?php

namespace App\Commands;

use App\Libs\Contracts\Abstracts\Parser;
use App\Models\File;
use http\Env;
use LaravelZero\Framework\Commands\Command;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * ES Client.
     *
     * @var Elasticsearch\ClientBuilder
     */
    protected $client;

    /**
     * Chunk size
     */
    protected $chunk;

    /**
     * Dump name.
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
     * Total documents in current index.
     *
     * @var bool
     */
    protected $total = 0;

    /**
     * Index sufix.
     *
     * @var int
     */
    protected $indexSufix = 1;

    /**
     * Non-processed lines output log.
     */
    const NONPROCESSED = 'non-processed.txt';


    public function __construct()
    {
        parent::__construct();
        $this->chunk = env('LEAKS_CHUNK');
    }

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
            $this->deleteESData();
            Storage::delete(Str::slug($this->name));
        }

        $this->countTotalDocuments();

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

    /**
     * Process a leak file.
     *
     * @param Parser $parser
     */
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
                Storage::append($this->getNonProcessedLogPath($filePath), $line, null);
                continue;
            }

            // Add the leak name
            $processedLine['leak'] = $this->name;

            if ($this->test) {
                print_r($processedLine);
            }

            // Prepare the ES request
            $indexData['_index'] = $this->getIndexName();
            if (env('ES_UNIQUE_ID')) {
                $indexData['_id'] = md5(json_encode($processedLine));
            }
            $data['body'][] = ['index' => $indexData];
            $data['body'][] = $processedLine;

            $total++;
            $this->total++;

            if ($total % $this->chunk == 0) {
                $this->insert($data);
                $data['body'] = [];
            }
        }
        fclose($handle);

        $bar->finish();
        $this->newLine();

        if ($total == 0) {
            $this->error('No lines were processed');
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

    /**
     * Insert bulk data in ES.
     *
     * @param array $data
     */
    private function insert($data)
    {
        if ($this->test) {
            return;
        }

        $res = $this->client->bulk($data);
        if ($res['errors']) {
            $this->error('ES Error!');
            print_r($res);
            die();
        }
    }

    /**
     * Delete all current leak documents from ES.
     */
    private function deleteESData()
    {
        try {
            $this->client->delete([
                'index' => $this->getIndexName(),
                'leak' => $this->name,
            ]);
        } catch (\Exception $e) {
            // 
        }
    }

    /**
     * Get the total documents in the current index.
     */
    private function countTotalDocuments()
    {
        try {
            $response = $this->client->count([
                'index' => $this->getIndexName(),
            ]);
            $this->total = $response['count'];
        } catch (\Exception $e) {
            //
        }
    }

    /**
     * Get the index name.
     * 
     * Format: <es_index>-<leak_name>-<count>
     *
     * @return string
     */
    private function getIndexName()
    {
        $name = env('ES_INDEX') . '-' . Str::slug($this->name);

        if ($this->total >= env('ES_MAX_DOCS_PER_INDEX')) {
            $this->total = 0;
            $this->indexSufix++;
        }

        return $name . '-' . str_pad($this->indexSufix, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the path for non-processed lines log.
     *
     * @param string $filePath
     * @return string
     */
    private function getNonProcessedLogPath($filePath)
    {
        return Str::slug($this->name) . '/' . $filePath . '/' . self::NONPROCESSED;
    }
}
