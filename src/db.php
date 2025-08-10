<?php

namespace natilosir\orm;

use Exception;
use InvalidArgumentException;
use PDO;

class DB {
    private static $connection;

    private static $table;

    private static $query;

    private static $ORDER;

    private static $limit;

    private static $distinct = false;

    private static $columns = '*';

    private static $params = [];

    private $data = [];

    public function __construct() {
        $database         = new Database();
        self::$connection = $database->getConnection();
    }

    public static function Table( $table ) {
        self::$table    = $table;
        self::$query    = '';
        self::$ORDER    = '';
        self::$limit    = '';
        self::$distinct = false;
        self::$columns  = '*';
        self::$params   = [];

        return new self();
    }

    public function distinct() {
        self::$distinct = true;

        return $this;
    }

    public static function select( $columns ) {
        if ( is_array($columns) ) {
            self::$columns = implode(', ', $columns);
        }
        else {
            self::$columns = $columns;
        }

        return new self();
    }

    public static function search( $conditions ) {
        if ( empty($conditions) ) {
            return new self();
        }

        foreach ( $conditions as $column => $value ) {
            if ( empty(self::$query) ) {
                self::$query = " WHERE $column LIKE :$column";
            }
            else {
                self::$query .= " AND $column LIKE :$column";
            }
            self::$params[":$column"] = "%$value%";
        }

        return new self();
    }

    public static function where($column, $operator = null, $value = null, $type = 'AND')
    {
        // Closure
        if ($column instanceof \Closure) {
            $nested = new self();
            $column($nested);
            if (!empty($nested::$query)) {
                if (empty(self::$query)) {
                    self::$query = " WHERE (" . preg_replace('/^ WHERE /', '', $nested::$query) . ")";
                } else {
                    self::$query .= " $type (" . preg_replace('/^ WHERE /', '', $nested::$query) . ")";
                }
                self::$params = array_merge(self::$params, $nested::$params);
            }
            return new self();
        }

        // [ ['col', '=', 'val'], ['col2', 'val2'] ]
        if (is_array($column) && isset($column[0]) && is_array($column[0])) {
            foreach ($column as $condition) {
                $col = $condition[0];
                if (count($condition) === 2) {
                    $op = '=';
                    $val = $condition[1];
                } else {
                    $op = $condition[1];
                    $val = $condition[2];
                }
                if (empty(self::$query)) {
                    self::$query = " WHERE $col $op :$col";
                } else {
                    self::$query .= " $type $col $op :$col";
                }
                self::$params[":$col"] = $val;
            }
            return new self();
        }

        // ['col' => 'val']
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                if (empty(self::$query)) {
                    self::$query = " WHERE $col = :$col";
                } else {
                    self::$query .= " $type $col = :$col";
                }
                self::$params[":$col"] = $val;
            }
            return new self();
        }

        // where('col', 'val') OR where('col', '>', 'val')
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $condition = "$column $operator :$column";
        if (empty(self::$query)) {
            self::$query = " WHERE $condition";
        } else {
            self::$query .= " $type $condition";
        }
        self::$params[":$column"] = $value;

        return new self();
    }

    public static function andWhere($column, $operator = null, $value = null)
    {
        return self::where($column, $operator, $value, 'AND');
    }

    public static function orWhere($column, $operator = null, $value = null)
    {
        return self::where($column, $operator, $value, 'OR');
    }


    public static function whereNotNull( $column, $type = 'AND' ) {
        $condition = "$column IS NOT NULL";

        if ( empty(self::$query) ) {
            self::$query = " WHERE $condition";
        }
        else {
            self::$query .= " $type $condition";
        }

        return new self();
    }

    public static function whereNull( $column, $type = 'AND' ) {
        $condition = "$column IS NULL";

        if ( empty(self::$query) ) {
            self::$query = " WHERE $condition";
        }
        else {
            self::$query .= " $type $condition";
        }

        return new self();
    }

    public static function orderBy( $column, $direction ) {
        if ( empty($direction) ) {
            $direction = $column;
            $column    = 'id';
        }

        $direction = strtoupper($direction);

        if ( $direction === 'MIN' ) {
            $direction = 'ASC';
        }
        elseif ( $direction === 'MAX' ) {
            $direction = 'DESC';
        }

        if ( !in_array($direction, [ 'ASC', 'DESC' ]) ) {
            throw new InvalidArgumentException("Invalid order direction: $direction");
        }

        self::$ORDER = " ORDER BY $column $direction";

        return new self();
    }

    public static function limit( $limit ) {
        self::$limit = " LIMIT $limit";

        return new self();
    }

    public static function first() {
        self::$limit = ' LIMIT 1';

        return self::get();
    }

    public function exists() {
        $result = self::first();
        if ( $result ) {
            return true;
        }
        {
            return false;
        }
    }

    public static function get() {
        $selectPart = self::$distinct ? 'SELECT DISTINCT ' . self::$columns : 'SELECT ' . self::$columns;
        $sql        = $selectPart . ' FROM ' . self::$table . self::$query . self::$ORDER . self::$limit;

        $stmt = self::$connection->prepare($sql);

        foreach ( self::$params as $key => $value ) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        if ( count($result) === 0 ) {
            return false;
        }

        return $result;
    }

    public function __set( $name, $value ) {
        $this->data[$name] = $value;
    }

    public function save( $params = null ) {
        try {
            if ( is_array($params) ) {
                $keys   = array_keys($params);
                $column = $keys[0];
                $id     = $params[$column];
            }
            else {
                $column = 'id';
                $id     = $params;
            }

            if ( empty($id) ) {
                $columns      = implode(', ', array_keys($this->data));
                $placeholders = ':' . implode(', :', array_keys($this->data));
                $sql          = 'INSERT INTO ' . self::$table . " ($columns) VALUES ($placeholders)";
                $stmt         = self::$connection->prepare($sql);

                foreach ( $this->data as $key => $value ) {
                    $stmt->bindValue(":$key", $value, is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                }

                if ( !$stmt->execute() ) {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception("Failed to execute INSERT query: " . $errorInfo[2]);
                }

                $lastId           = self::$connection->lastInsertId();
                $this->data['id'] = $lastId;

                return self::Table(self::$table)->where('id', $lastId)->first();
            }
            else {
                $set = '';
                foreach ( $this->data as $col => $value ) {
                    $set .= "$col = :$col, ";
                }
                $set = rtrim($set, ', ');
                $sql = 'UPDATE ' . self::$table . " SET $set WHERE $column = :id";

                $stmt = self::$connection->prepare($sql);
                foreach ( $this->data as $col => $value ) {
                    $stmt->bindValue(":$col", $value, is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                }
                $stmt->bindValue(':id', $id);

                if ( !$stmt->execute() ) {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception("Failed to execute UPDATE query: " . $errorInfo[2]);
                }

                return self::Table(self::$table)->where($column, $id)->first();
            }
        } catch ( Exception $e ) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function count() {
        $sql = 'SELECT COUNT(*) FROM ' . self::$table . self::$query . self::$ORDER . self::$limit;

        $stmt = self::$connection->prepare($sql);

        foreach ( self::$params as $key => $value ) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function insert( $data ) {
        if ( is_array($data) ) {
            $columns      = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $stmt         = self::$connection->prepare('INSERT INTO ' . self::$table . " ($columns) VALUES ($placeholders)");
            foreach ( $data as $key => $value ) {
                $stmt->bindValue(":$key", $value);
            }

            return $stmt->execute();
        }
        else {
            $stmt = self::$connection->prepare('INSERT INTO ' . self::$table . ' (name) VALUES (:name)');
            $stmt->bindValue(':name', $data);

            return $stmt->execute();
        }
    }

    public function create( $data ) {
        return self::Table(self::$table)->insert( $data );
    }

    public function update( $params, $data = null ) {
        if ( $data === null && is_array($params) ) {
            $data   = $params;
            $params = null;
        }

        if ( empty(self::$query) ) {
            if ( is_array($params) ) {
                foreach ( $params as $column => $value ) {
                    self::$query                .= empty(self::$query) ? " WHERE $column = :w_$column" : " AND $column = :w_$column";
                    self::$params[":w_$column"] = $value;
                }
            }
            elseif ( $params !== null ) {
                self::$query           = ' WHERE id = :w_id';
                self::$params[':w_id'] = $params;
            }
            else {
                throw new Exception('Missing WHERE clause for update.');
            }
        }

        $set = '';
        foreach ( $data as $column => $value ) {
            $set                        .= "$column = :s_$column, ";
            self::$params[":s_$column"] = $value;
        }
        $set = rtrim($set, ', ');

        $query = 'UPDATE ' . self::$table . ' SET ' . $set . self::$query;

        $stmt = self::$connection->prepare($query);

        foreach ( self::$params as $key => $value ) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    public static function createOrFirst( $conditions, $data = [] ) {
        $record = self::Table(self::$table)->where($conditions)->first();

        if ( $record ) {
            $record->is_updated = false;
            return $record;
        }

        $instance = new self();
        $allData  = array_merge($conditions, $data);

        foreach ( $allData as $key => $value ) {
            $instance->$key = $value;
        }

        $instance->save();
        $id = $instance->data['id'];

        $final             = self::Table(self::$table)->where('id', $id)->first();
        $final->is_updated = true;

        return $final;
    }

    public static function createOrUpdate( $conditions, $data = [] ) {
        $record = self::Table(self::$table)->where($conditions)->first();

        $instance = new self();
        $allData  = array_merge($conditions, $data);

        foreach ( $allData as $key => $value ) {
            $instance->$key = $value;
        }

        if ( $record ) {
            $instance->save($conditions);

            $conditionKey = array_key_first($conditions);
            $id           = $conditions[$conditionKey];

            $final             = self::Table(self::$table)->where($conditionKey, $id)->first();
            $final->is_updated = true;

            return $final;
        }
        else {
            $instance->save();

            $id                = $instance->data['id'];
            $final             = self::Table(self::$table)->where('id', $id)->first();
            $final->is_updated = false;

            return $final;
        }
    }

    public static function updateOrInsert( $conditions, $data ) {
        return self::createOrUpdate($conditions, $data);
    }

    public function delete( $params = null ) {
        if ( empty(self::$query) ) {
            if ( is_int($params) ) {
                self::$query         = ' WHERE id = :id';
                self::$params[':id'] = $params;
            }
            elseif ( is_array($params) ) {
                foreach ( $params as $column => $value ) {
                    self::$query                .= empty(self::$query) ? " WHERE $column = :w_$column" : " AND $column = :w_$column";
                    self::$params[":w_$column"] = $value;
                }
            }
            elseif ( $params === null ) {
                throw new Exception('Missing WHERE clause for delete.');
            }
        }
        $sql  = 'DELETE FROM ' . self::$table . self::$query;
        $stmt = self::$connection->prepare($sql);

        foreach ( self::$params as $key => $value ) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    public static function query( $sql ) {
        self::table('');
        $stmt = self::$connection->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function SQL() {
        $selectPart = self::$distinct ? 'SELECT DISTINCT ' . self::$columns : 'SELECT ' . self::$columns;

        return $selectPart . ' FROM ' . self::$table . self::$query . self::$ORDER . self::$limit;
    }
}
