# LeaksDB

This project **pretend** to parse public leak dumps (like Adobe, Dropbox...) and store those in Elasticsearch.

## Usage

## Setup

1) Run `composer install`.
2) Adjust the `.env` (use `.env.example` as source) with your ES settings.

### Env
* `ES_URL` URL for the Elasticsearch instance.
* `ES_INDEX` Elasticsearch index name to use.
* `ES_UNIQUE_ID` If set to True, an unique ID will be generated on each document, based in the document fields, to prevent duplicated records. However, this will slow down the import considerably.

## Importing dumps

Just run the following command (and wait a few days, since is PHP & single-thread script after all):

```
php leaksdb import <leak_name> <dump_path> <parser_class>
```

Where:
* `<leak_name>` leak name (stored as ES field).
* `<dump_path>` folder containing the uncompressed leak dump files.
* `<parser_class>` PHP Class in `Libs/Parsers` to use (for generic *username:password* or *email:password*, use `UserPass` parser).

Optional parameters:
* `--delete` Will delete all the ES documents of that leak, based on the leak name (doesn't use the sqlite db).
* `--test` Will print the parsed data without performing DB changes.

The script will map all the processed files in `database/database.sqlite` file. An already migrated database is included in the repository. If a record exists in the database, the file will be ignored.

## Dev & Debug

### Using Xdebug configured docker container for development

If you prefer to develop with full access to internal state of the script, you can use provided docker environment with Xdebug. 
To build the image:

```sh
docker-compose -f docker-compose-dev.yml up  
```

That will use the `Dockerfile-dev` to bootstrap and build dev image with Xdebug turned on.


By default it will configure container with PHP 7.4-cli and Xdebug 2.8.1 sending info to host port 9000 (Xdebug 3.x uses port 9003 by default, so please remember when changing).

If you IDE requires `xdebug.idekey` to be set, please set `XDEBUG_IDEKEY=` in the `.env` file. By default this variable is empty.

To run the commands in the container with Xdebug:

```sh
docker-compose -f docker-compose-dev.yml run --rm -w /app leaksdb-dev php leaksdb 
```

which should print help text for the `leaksdb` 
```
Leaksdb  unreleased

USAGE: leaksdb <command> [options] [arguments] 
(...)
```

#### Configuring Xdebug in Visual Studio Code 
Install `PHP Debug` plugin (`felixfbecker.php-debug`).
Go to `Run` -> `Open Configurations`, which should bring the `launch.json` config file.

in the `"name": "Listen for Xdebug",` section add `"port": 9000,` if not present, and add mappings

```json
            "pathMappings": {
                "/app": "${workspaceRoot}"
            }
```

Mark some break points in your script and press `F5` or go to `Run` -> `Start Debugging`. That should bring out the debug console and start listening on the port 9000 for Xdebug. 

Now run your script and start debugging when the code hit the break point. 

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

In order to play around, dev or debug when importing dumps, you can generate sample files from a dump folder.
The `create-samples` script will generate a copy of a dump directory (recursively) but with only a number of lines for each file.

```
php leaksdb create-samples <lines> <dump_path> <output_path>
```

Where:
* `<lines>`: Number of lines to store.
* `<dump_path>`: folder containing the uncompressed leak dump files.
* `<output_path>`: folder to store the samples.