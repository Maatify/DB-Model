<?php
/**
 * @copyright ©2023 Maatify.dev
 * @author    Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since     2023-05-21 4:17 PM
 * @link      https://www.maatify.dev Maatify.com
 * @link      https://github.com/Maatify/DB-Model  view project on GitHub
 * @Project   DB-Model
 */

declare(strict_types = 1);

namespace Maatify\Model;

use Maatify\Json\Json;
use Maatify\Logger\Logger;
use PDO;

/**
 * @mixin PDO
 */
// * @mixin Connection
class DB
{
    private PDO $pdo;


//    private string $charset = 'utf8';
    private string $charset = 'utf8mb4';
//    private string $charset = 'utf8_general_ci';

//    private Connection $connection;

    public function __construct(array $config = [])
    {
        $defaultOptions = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
//            $this->connection = DriverManager::getConnection($config);

            $this->pdo = new PDO(
//                'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=' . $this->charset . ';character_set_results=;character_set_client=' . $this->charset . ';character_set_connection=' . $this->charset . ';',
                'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=' . $this->charset,
                $config['user'],
                $config['password'],
                $config['options'] ?? $defaultOptions
            );


//            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
//            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
//            throw new \PDOException($e->getMessage(), (int) $e->getCode());
            Logger::RecordLog([$e->getMessage(), (int) $e->getCode(), 'db_connection']);

            Json::DbError(__LINE__);
        }
    }

    public function __call(string $name, array $arguments)
    {
//        if($this->pdo){
            try {
                return call_user_func_array([$this->pdo, $name], $arguments);
            }catch (\Exception $exception){
                Logger::RecordLog($exception, 'db_call_func');
            }
//        }
        Json::DbError(__LINE__);
        return false;
//        return call_user_func_array([$this->connection, $name], $arguments);
    }
}
