# DB-Model

maatify.dev MySql Database PDO Model handler, known by our team

# Installation

```shell
composer require maatify/db-model
```

# Usage
#### Create DbConnector Connection Class Extends Model
```PHP
<?php

namespace Maatify\files;

use Maatify\Json\Json;
use Maatify\Logger\Logger;
use Maatify\Model\DB;
use Maatify\Model\Model;
use PDOException;

abstract class DbConnector extends Model
{
    private static DB $connection;

    public function __construct()
    {
        if(empty(static::$connection)){
            try {
                static::$connection = new DB([
                    'host'     => __DB_HOST__,
                    'user'     => __DB_USER__,
                    'password' => __DB_PASSa__,
                    'dbname'   => __DB_DATABASE__,
                    'charset'  => 'utf8mb4',
                ]);
            }
            catch (PDOException $e){
                Logger::RecordLog([$e->getMessage(), (int)$e->getCode()], 'app_connections');
                Json::DbError(__LINE__);
            }
        }
        parent::__construct();
        $this->db = static::$connection;
    }
}
```
#### Create Connection Table Handler Class Extends DbConnector
```PHP
<?php

class Info extends DbConnector
{
    protected string $tableName = 'info';

    public function Insert(int $id, string $name): void
    {
        $this->Add(
            [
                'id'   => $id,
                'name' => $name,
            ]
        );
    }

    public function SelectAll(): array
    {
        return $this->Rows($this->tableName);
    }

    public function SelectById(int $id): array
    {
        return $this->Rows($this->tableName, '*', '`id` = ?', [$id]);
    }

    public function UpdateNameByID(int $id, string $name): bool
    {
        return $this->Edit(['name'=>$name], '`id` = ?', [$id]);
    }
}
```
