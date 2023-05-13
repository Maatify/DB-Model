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
use Maatify\PostValidator\PostValidator;
use PDOException;
abstract class Model extends PDOBuilder
{

    //========================================================================

    protected PostValidator $postValidator;
    protected int $limit = 0;
    protected int|float $offset = 0;
    protected int $id = 0;
    protected int $ct_id = 0;
    protected int $admin_id = 0;
    protected int $next = 0;
    protected int $pagination = 0;
    protected int $previous = 0;

    protected int $count = 0;
    public function __construct()
    {
        $this->postValidator = PostValidator::obj();
        $page = max(((int)$this->postValidator->Optional('page', 'page') ?: 1),1);
        $this->limit = max(((int)$this->postValidator->Optional('limit', 'limit') ?: 25), 1);
        $this->pagination = $page - 1;
        if($this->pagination > 0){
            $this->previous = $this->pagination;
        }
        $this->offset = $this->pagination * $this->limit;
    }

    protected function PaginationNext(int $count): int
    {
        if($this->pagination+1 >= $count / $this->limit){
            return 0;
        }else{
            return $this->pagination+2;
        }
    }

    protected function PaginationLast(int $count): int
    {
        $pages = $count / $this->limit;
        if ((int) $pages == $pages) {
            $page = $pages;
        }else{
            $page = (int)$pages+1;
        }
        if($count && $this->PaginationPrevious()+1 > $page){
            Json::Incorrect('page');
        }
        return $page;
    }

    protected function PaginationPrevious(): int
    {
        return $this->previous;
    }

    protected function PostedID(): int
    {
        return $this->id = (int)$this->postValidator->Require('id', 'int');
    }

    protected function PostedUserID(): int
    {
        $this->admin_id = (int)$this->postValidator->Require('user_id', 'int');
        if(!\App\DB\Tables\Admin\Admin::obj()->ExistID($this->admin_id)){
            Json::Incorrect('id', 'user id not found');
        }
        return $this->admin_id;
    }

    protected function AddWherePagination(): string
    {
        return " limit $this->limit OFFSET $this->offset ";
    }

    protected function PaginationHandler(int $count, array $data, array $others = []): array
    {
        return [
            'count'=>$count,
            'page_previous'=>$this->PaginationPrevious(),
            'page_next'=>$this->PaginationNext($count),
            'page_last'=>$this->PaginationLast($count),
            'page_limit'=>$this->limit,
            'page_current'=>$this->pagination+1,
            'data'=>$data,
            'other' => $others
        ];
    }

    protected function JsonHandlerWithOther(array $data, array $other = []): void
    {
        Json::Success(
            [
                'data'  => $data,
                'other' => $other,
            ]
        );
    }

    protected function PaginationRows(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        return $this->Rows($tableName,
            $columns,
            $where . ' ' . $this->AddWherePagination(),
            $wheresVal);
    }

    protected function PaginationThisTableRows(string $where = '', array $wheresVal = []): array
    {
        return $this->Rows($this->tableName,
            '*',
            $where . ' ' . $this->AddWherePagination(),
            $wheresVal);
    }
    protected function ExistIDThisTable(int $id): bool
    {
        return $this->RowIsExistThisTable('`id` = ? ', [$id]);
    }
    protected function RowThisTableByID(int $id): array
    {
        return $this->RowThisTable('*', '`id` = ? ', [$id]);
    }

    //========================================================================

    protected function TableName(): string
    {
        return $this->tableName;
    }

    protected function ColsJoin(): string
    {
        if(empty($this->tableAlias)){
            $this->tableAlias = $this->tableName;
        }

        $query = '';
        /*
        IFNULL(`$tb_requirement`.`q_en`,0) as q_en,
    */
        foreach ($this->cols as $col => $type){
            if($col != 'id'){
                $query .= "IFNULL(`$this->tableName`.`$col`," . ($this->ColJoinTypeToString($type) === '' ? "''" : $this->ColJoinTypeToString($type)) . ") as $this->tableAlias" . '_' . $col . ', ';
            }
        }
        return rtrim($query, ', ');

    }

    protected function MaxIDThisTable(): int
    {
        return (int)$this->ColThisTable('`id`', '`id` > ? ORDER BY id DESC LIMIT 1', [0]);
    }

    protected function Row(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->FetchRow($this->PrepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            return $this->LogError($e, 'Row '. $tableName .' where '.$where . ' ' . $columns, __LINE__, $wheresVal);
        }
    }

    protected function RowThisTable(string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->Row($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            return $this->LogError($e, 'RowThisTable '.$this->tableName.' where '.$where, __LINE__, $wheresVal);
        }
    }

    protected function ColThisTable(string $columns = '*', string $where = '', array $wheresVal = []): string
    {
        try {
            return (string) $this->Col($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            $this->LogError($e, 'ColThisTable '.$this->tableName.' where '.$where, __LINE__, $wheresVal);
            return '';
        }
    }

    protected function Col(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): string
    {
        try {
            return (string) $this->FetchCol($this->PrepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            $this->LogError($e, 'Col '.$this->tableName.' where '.$where, __LINE__, $wheresVal);
            return '';
        }
    }

    protected function RowsThisTable(string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->Rows($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            return $this->LogError($e, 'RowsThisTable '.$this->tableName.' where '.$where, __LINE__, $wheresVal);
        }
    }

    protected function Rows(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->FetchRows($this->PrepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            return $this->LogError($e, 'Rows '.$this->tableName.' where '.$where . PHP_EOL, __LINE__, $wheresVal);
        }
    }

    protected function RowIsExistThisTable(string $where = '', array $wheresVal = []): bool
    {
        return (bool) $this->ColThisTable('*', $where, $wheresVal);
    }

    protected function RowISExist(string $tableName, string $where = '', array $wheresVal = []): bool
    {
        return (bool) $this->Col($tableName, '*', $where, $wheresVal);
    }


    // ======================= Json =======================


    protected function JsonCol(array $array): string
    {
        $str = "(CONCAT(
            '[',GROUP_CONCAT(distinct CONCAT( '{";
        foreach ($array as $key=>$value){
            $str .="\"$key\":\"', ifNull($value, ''), '\",";
        }
        $str = rtrim($str, ',');
        $str .="}') ),
            ']'
        ))";
        return $str;
    }

    protected function JsonColLimit(array $array, int  $limit): string
    {
        $str = "(CONCAT(
            '[',GROUP_CONCAT(distinct CONCAT( '{";
        foreach ($array as $key=>$value){
            $str .="\"$key\":\"', ifNull($value, ''), '\",";
        }
        $str = rtrim($str, ',');
        $str .="}') LIMIT $limit),
            ']'
        ))";
        return $str;
    }

    protected function JsonColRandomLimit(array $array, int  $limit): string
    {
        $str = "(CONCAT(
            '[',GROUP_CONCAT(distinct CONCAT( '{";
        foreach ($array as $key=>$value){
            $str .="\"$key\":\"', ifNull($value, ''), '\",";
        }
        $str = rtrim($str, ',');
        $str .="}') ORDER BY RAND() LIMIT $limit),
            ']'
        ))";
        return $str;
    }


}