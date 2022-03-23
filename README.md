# MySql Backup
This is a simple PHP library for exporting table structures and data, similar to the PhpMyAdmin export feature.

## Features

- Export table structure
- Export data in the form of transactional SQL query
- Save and download the database export


## Installation

Install the dependencies and devDependencies and start the server.

```sh
composer require coding-sniper/mysql-backup
```

## Usage

```php
use CodingSniper\MysqlBackup\Backup;
$backup = new Backup($db,$user,$pass); // pass db credentials here
$backup->export(); // exports and downloads yoour db in the form of dbname.sql
```

## License

MIT

**Free Software, Hell Yeah!**