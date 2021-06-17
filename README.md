# LeaksDB

This project **pretend** to parse public leak dumps (like Adobe, Dropbox...) and store those in Elasticsearch.

## Usage

## Setup

1) Run `composer install`.
2) Adjust the `.env` (use `.env.example` as source) with your ES settings.

## Importing dumps

Just run the following command (and wait a few days, since is PHP & single-thread script after all):

```
php leaksdb import <leak_name> <dump_path> <parser_class>
```

Where:
* `<leak_name>` leak name (stored as ES field).
* `<dump_path>` folder containing the uncompressed leak dump files.
* `<parser_class>` PHP Class in `Libs/Parsers` to use.

Optional parameters:
* `--delete` Will delete all the ES documents of that leak, based on the leak name.
* `--test` Will print the parsed data without performing DB changes.

## Dev & Debug

### Elaticsearch + Kibana

For easier development, you can run a local dockerized ES+Kibana with:

```
docker-compose -f docker-compose-es-kibana.yml up
```

### Non-parsed lines

Some times, the dumps contain lines that are in different formats or that fails when being parsed.
The `import` command will create a `non-processed.txt file` containing all the lines that could not be parsed.
That file will be **deleted** on each run!

### Create dump samples

In order to play around, dev or debbug when importing dumps, you can generate sample files from a dump folder.
The `create-samples` script will generate a copy of a dump directory (recursivelly) but with only a number of lines for each file.

```
php leaksdb create-samples <lines> <dump_path> <output_path>
```

Where:
* `<lines>`: Number of lines to store.
* `<dump_path>`: folder containing the uncompressed leak dump files.
* `<output_path>`: folder to store the samples.