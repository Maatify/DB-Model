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

namespace Maatify\Model;

use Maatify\Json\Json;
use Maatify\Logger\Logger;
use PDOException;

abstract class PDOBuilder
{

    protected DB $db;

    protected string $tableName;
    protected string $tableAlias;

    protected array $cols = [];
    // as key => 0, 1, 2 :  0 is string, 1 is int, 2 is float

    //          ['id'=>1, 'name_en'=>0, 'name_ar'=>0, 'association_id'=>1, 'salary_percentage'=>1, 'required_all'=>1, 'status'=>1];

    protected function ColJoinTypeToString(int $type): float|int|string
    {
        return match ($type) {
            1 => 0,
            2 => 0.00,
            default => '',
        };
    }

    public function Edit(array $colsValues, string $where, array $wheresVal): bool
    {
        try {
            $queryString = 'UPDATE `' . $this->tableName . '` SET ';
            $values = [];
            foreach ($colsValues as $col => $value) {
                $queryString .= " `$col` = ? , ";
                $values[] = $value;
            }
            $values = array_merge($values, $wheresVal);
            $queryString = rtrim($queryString, ", ");
            $queryString .= ' WHERE ' . $where . ';';

            return (bool)$this->ExecuteStatement($queryString, $values);
        } catch (PDOException $e) {
            $this->LogError(
                $e,
                'Update ' . $this->tableName . ' ' . $where,
                __LINE__,
                array_merge($colsValues, $wheresVal)
            );

            return false;
        }
    }

    protected function Add(array $colsValues): int
    {
        try {
            $queryString = 'INSERT INTO `' . $this->tableName . '` (';
            $cols = '';
            $values = [];
            foreach ($colsValues as $col => $value) {
                $cols .= '`' . $col . '`,';
                $values[] = $value;
            }
            $queryString .= rtrim($cols, ',') . ") VALUES (";
            $queryString .= str_repeat('?,', sizeof($colsValues));
            $queryString = rtrim($queryString, ',') . ")";
            $queryString .= ';';
            $this->ExecuteStatement($queryString, $values);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->LogError($e, 'Insert', __LINE__, $colsValues);

            return 0;
        }
    }

    protected function Delete(string $where, array $wheresVal): bool
    {
        try {
            $queryString = 'DELETE FROM `' . $this->tableName . '` WHERE ' . $where;
            $queryString .= ';';

            return (bool)$this->ExecuteStatement($queryString, $wheresVal);
        } catch (PDOException $e) {
            $this->LogError($e, 'Delete ' . $this->tableName . ' ' . $where, __LINE__, $wheresVal);

            return false;
        }
    }

    protected function CountThisTableRows(string $column = '*', string $where = '', array $wheresVal = []): int
    {
        return $this->CountTableRows($this->tableName, $column, $where, $wheresVal);
    }

    protected function CountTableRows(string $table, string $column = '*', string $where = '', array $wheresVal = []): int
    {
        try {
            $queryString = 'SELECT count(' . $column . ') as count FROM ' . $table . ($where ? ' WHERE ' . $where : '') . ';';
            //            Logger::RecordLog($queryString);
            $query = $this->db->prepare($queryString);
            $query->execute($wheresVal);

            return (int)$query->fetchColumn();
        } catch (PDOException $e) {
            $this->LogError($e, 'CountTableRows: ' . $queryString ?? '', __LINE__);

            return 0;
        }
    }

    protected function SortTable(string $column): bool
    {
        try {
            $queryString = 'SET @num := 0; UPDATE ' . $this->tableName . ' SET `' . $column . '` = @num := (@num+1); ALTER TABLE ' . $this->tableName . ' AUTO_INCREMENT = 1;';

            return $this->ExecuteStatement($queryString);
        } catch (PDOException $e) {
            $this->LogError($e, 'SortTable: ' . $queryString ?? '', __LINE__);

            return false;
        }
    }

    protected function PrepareSelect(string $tablesName, string $columns = '*', string $where = '', array $wheresVal = []): \PDOStatement
    {
        $queryString = 'SELECT ' . $columns;
        $queryString .= ' FROM ' . $tablesName;
        if (! empty($where)) {
            $queryString .= ' WHERE ' . $where;
        }

        //        Logger::RecordLog($queryString);
        return $this->ExecuteStatement($queryString, $wheresVal);
    }

    //    [['table', 'where', 'as']]
    protected function SelectMultiCounts(array $select_queries = []): array
    {
        $queryString = 'SELECT ';
        foreach ($select_queries as $query) {
            $queryString .= "(SELECT COUNT(`id`) FROM   " . $query[0] . " WHERE " . $query[1] . ") AS " . $query[2] . ", ";
        }
        $queryString = rtrim($queryString, ', ');
        try {
            return $this->FetchRow($this->ExecuteStatement($queryString));
        } catch (PDOException $e) {
            return $this->LogError($e, 'SelectMultiCounts ' . $queryString, __LINE__, []);
        }
    }

    protected function FetchRow(\PDOStatement $query): array
    {
        return $query->fetch() ? : [];
    }

    protected function FetchRows(\PDOStatement $query): array
    {
        return $query->fetchAll() ? : [];
    }

    protected function FetchCol(\PDOStatement $query): string
    {
        return $query->fetchColumn() ? : '';
    }

    protected function LogError(PDOException $e, string $queryString, int $line, array $wheresVal = []): array
    {
        Logger::RecordLog(['query' => $queryString ?? '', 'wheresVal' => $wheresVal, 'line' => $line, 'exception' => $e,], 'db_errors');

        return [];
    }

    private function ExecuteStatement(string $queryString, array $values = []): bool|\PDOStatement
    {
        $queryString = preg_replace(
            '!\s+!',
            ' ',
            preg_replace('~[\r\n]+~', '', $queryString)
        );
        $query = $this->db->prepare($queryString);
        if ($values) {
            $values = array_map([$this, 'HandleHtmlTags'], $values);
        }
        $query->execute($values);

        return $query;
    }

    private static function HandleHtmlTags($val)
    {
        if (gettype($val) == 'string') {
            return htmlspecialchars(
                stripslashes(
                    trim(str_replace(array("'", "&quot;", "&#039;"),
                        "’",
                        /*str_replace(array(' ', ','), '', $val)*/
                        str_replace(array(','), '', $val)
                    ))
                ),
                ENT_QUOTES,
                'UTF-8');
        }

        return $val;
    }

    protected function AlertTable($fullAlert): bool
    {
        return $this->SpecialQuery($fullAlert);
    }

    private function SpecialQuery($query): bool
    {
        $queryString = preg_replace('!\s+!', ' ', preg_replace('~[\r\n]+~', '', $query));
        try {
            $query = $this->db->prepare($queryString);

            return (bool)$this->ExecuteStatement($query);
            //            return $query->execute($queryString);
        } catch (PDOException $e) {
            $this->LogError($e, 'SpecialQuery ' . $queryString, __LINE__, []);
            Json::DbError(__LINE__);

            return false;
        }
    }
}