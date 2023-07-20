<?php
/**
 * @copyright   ©2023 Maatify.dev
 * @Liberary    DB-Model
 * @Project     DB-Model
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2023-05-21 4:17 PM
 * @see         https://www.maatify.dev Maatify.com
 * @link        https://github.com/Maatify/DB-Model  view project on GitHub
 * @link        https://github.com/Maatify/Logger (maatify/logger)
 * @link        https://github.com/Maatify/Json (maatify/json)
 * @link        https://github.com/Maatify/PostValidator (maatify/post-validator)
 * @copyright   ©2023 Maatify.dev
 * @note        This Project using for MYSQL PDO (PDO_MYSQL).
 * @note        This Project extends other libraries maatify/logger, maatify/json, maatify/post-validator.
 *
 * @note        This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

declare(strict_types = 1);

namespace Maatify\Model;

use Exception;
use Maatify\Json\Json;
use Maatify\Logger\Logger;
use PDO;
use PDOException;

/**
 * @mixin PDO
 */
class DB
{
    private PDO $pdo;
    protected string $charset = 'utf8mb4'; // 'utf8', 'utf8_general_ci'
    public function __construct(array $config = [])
    {
        $defaultOptions = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if(!empty($config['charset'])){
            $this->charset = $config['charset'];
        }

        try {
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
        } catch (PDOException $e) {
            Logger::RecordLog([$e->getMessage(), (int) $e->getCode(), 'db_connection']);
            Json::DbError(__LINE__);
        }
    }

    public function __call(string $name, array $arguments)
    {
        try {
            return call_user_func_array([$this->pdo, $name], $arguments);
        }catch (Exception $exception){
            Logger::RecordLog($exception, 'db_call_func');
        }
        Json::DbError(__LINE__);
        return false;
    }
}
