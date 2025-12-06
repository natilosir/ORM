<?php

namespace natilosir\orm;

use Exception;
use natilosir\bot\Log;
use PDO;

class DB {
    public ?object  $modelInstance = null;
    private string  $table;
    private ?string $modelClass    = null;
    private array   $select        = [ '*' ];
    private bool    $isDistinct    = false;
    private array   $orders        = [];
    private ?int    $limit         = null;

    private array $wheres   = [];
    private array $bindings = [];

    private array $with = [];

    public function __construct( string $table ) {
        $this->table = $table;
    }

    public function loadModel( object $model ): self {
        $this->modelInstance = $model;
        return $this;
    }

    public function with( ...$relations ): self {
        foreach ( $relations as $r ) {
            $this->with[] = $r;
        }
        return $this;
    }

    public function dd() {
        $this->lg();
        die();
    }

    public function lg() {
        $payload = [
            'type'     => 'query',
            'sql'      => $this->SQL(),
            'bindings' => $this->bindings,
            'wheres'   => $this->wheres,
            'table'    => $this->table,
            'model'    => $this->modelClass,
        ];
        Log::debug($payload);
    }

    public function SQL(): string {
        return $this->buildSQL();
    }

    private function buildSQL(): string {
        $sql = "SELECT ";

        if ( $this->isDistinct ) $sql .= "DISTINCT ";

        $sql .= implode(', ', $this->select);
        $sql .= " FROM {$this->table}";

        if ( !empty($this->wheres) ) {
            $sql .= " WHERE " . $this->compileWhereTree($this->wheres);
        }

        foreach ( $this->orders as [$col, $dir] ) {
            $sql .= " ORDER BY $col $dir,";
        }
        if ( !empty($this->orders) ) {
            $sql = rtrim($sql, ',');
        }

        if ( $this->limit ) $sql .= " LIMIT {$this->limit}";

        return $sql;
    }

    private function compileWhereTree( array $nodes ): string {
        $sql = '';

        foreach ( $nodes as $i => $w ) {
            $bool = ( $i === 0 ? '' : strtoupper($w['boolean']) . ' ' );

            switch ( $w['type'] ) {
                case 'basic':
                    $sql .= "$bool{$w['column']} {$w['operator']} :{$w['value']} ";
                    break;

                case 'in':
                    $in  = implode(', ', array_map(fn( $k ) => ":$k", $w['values']));
                    $sql .= "$bool{$w['column']} IN ($in) ";
                    break;

                case 'not_in':
                    $in  = implode(', ', array_map(fn( $k ) => ":$k", $w['values']));
                    $sql .= "$bool{$w['column']} NOT IN ($in) ";
                    break;

                case 'between':
                    $sql .= "$bool{$w['column']} BETWEEN :{$w['values'][0]} AND :{$w['values'][1]} ";
                    break;

                case 'not_between':
                    $sql .= "$bool{$w['column']} NOT BETWEEN :{$w['values'][0]} AND :{$w['values'][1]} ";
                    break;

                case 'raw':
                    $sql .= "$bool{$w['sql']} ";
                    break;
                case 'not_null':
                    $sql .= "$bool{$w['column']} IS NOT NULL";
                    break;

                case 'group':
                    $inside = $this->compileWhereTree($w['wheres']);
                    $sql    .= "$bool($inside) ";
                    break;
            }
        }

        return trim($sql);
    }

    public function log() {
        $this->lg();
    }

    public function select( $columns ): self {
        if ( is_string($columns) ) $columns = explode(',', $columns);
        $this->select = array_map('trim', $columns);
        return $this;
    }

    public function distinct(): self {
        $this->isDistinct = true;
        return $this;
    }

    public function orderBy( string $col, string $dir = 'ASC' ): self {
        $this->orders[] = [ $col, strtoupper($dir) ];
        return $this;
    }

    public function orWhereIn( string $column, array $values ): self {
        return $this->whereIn($column, $values, 'or');
    }

    public function whereIn( string $column, array $values, string $boolean = 'and' ): self {
        if ( count($values) === 0 ) {
            return $this->addWhere([
                'type'     => 'raw',
                'boolean'  => $boolean,
                'sql'      => '1 = 0',
                'bindings' => [],
            ], $boolean);
        }

        $placeholders = [];
        foreach ( $values as $v ) {
            $key            = $this->bind($v);
            $placeholders[] = ':' . $key;
        }

        return $this->addWhere([
            'type'     => 'raw',
            'boolean'  => $boolean,
            'sql'      => $column . ' IN (' . implode(', ', $placeholders) . ')',
            'bindings' => [],
        ], $boolean);
    }

    private function addWhere( array $node, string $boolean ): self {
        $node['boolean'] = strtolower($boolean);
        $this->wheres[]  = $node;
        return $this;
    }

    private function bind( $value ): string {
        $k                  = 'b' . count($this->bindings);
        $this->bindings[$k] = $value;
        return $k;
    }

    public function whereArray( array $conds ): self {
        foreach ( $conds as $column => $value ) {
            $this->where($column, $value);
        }
        return $this;
    }

    public function where( $col, $op = null, $value = null, $boolean = 'and' ): self {
        if ( func_num_args() === 2 ) {
            $value = $op;
            $op    = '=';
        }
        $key = $this->bind($value);

        return $this->addWhere([
            'type'     => 'basic',
            'column'   => $col,
            'operator' => $op,
            'value'    => $key,
        ], $boolean);
    }

    public function whereNotIn( string $col, array $values, string $boolean = 'and' ): self {
        $keys = $this->bindArray($values);
        return $this->addWhere([
            'type'   => 'not_in',
            'column' => $col,
            'values' => $keys,
        ], $boolean);
    }

    private function bindArray( array $values ): array {
        return array_map(fn( $v ) => $this->bind($v), $values);
    }

    public function whereBetween( string $col, array $vals, string $boolean = 'and' ): self {
        $keys = $this->bindArray($vals);
        return $this->addWhere([
            'type'   => 'between',
            'column' => $col,
            'values' => $keys,
        ], $boolean);
    }

    public function orWhereNotNull( string $column ): self {
        return $this->whereNotNull($column, 'or');
    }

    public function whereNotNull( string $column, string $boolean = 'and' ): self {
        return $this->addWhere([
            'type'    => 'raw',
            'boolean' => $boolean,
            'sql'     => "{$column} IS NOT NULL",
        ], $boolean);
    }

    public function whereNotBetween( string $col, array $vals, string $boolean = 'and' ): self {
        $keys = $this->bindArray($vals);
        return $this->addWhere([
            'type'   => 'not_between',
            'column' => $col,
            'values' => $keys,
        ], $boolean);
    }

    public function whereDate( $col, $val, $boolean = 'and' ): self {
        return $this->rawCompare("DATE($col)", '=', $val, $boolean);
    }

    private function rawCompare( $raw, $op, $value, $boolean ) {
        $key = $this->bind($value);
        return $this->addWhere([
            'type' => 'raw',
            'sql'  => "$raw $op :$key",
        ], $boolean);
    }

    public function whereMonth( $col, $val, $boolean = 'and' ): self {
        return $this->rawCompare("MONTH($col)", '=', $val, $boolean);
    }

    public function whereYear( $col, $val, $boolean = 'and' ): self {
        return $this->rawCompare("YEAR($col)", '=', $val, $boolean);
    }

    public function whereDay( $col, $val, $boolean = 'and' ): self {
        return $this->rawCompare("DAY($col)", '=', $val, $boolean);
    }

    public function whereRaw( string $sql, array $bindings = [], string $boolean = 'and' ): self {
        foreach ( $bindings as $v ) {
            $key = $this->bind($v);
            $sql = preg_replace('/\?/', ':' . $key, $sql, 1);
        }

        return $this->addWhere([
            'type' => 'raw',
            'sql'  => $sql,
        ], $boolean);
    }

    public function orWhereGroup( callable $cb ): self {
        return $this->whereGroup($cb, 'or');
    }

    public function whereGroup( callable $callback, string $boolean = 'and' ): self {
        $clone = new static($this->table);
        $callback($clone);

        foreach ( $clone->bindings as $k => $v ) {
            $this->bindings[$k] = $v;
        }

        return $this->addWhere([
            'type'   => 'group',
            'wheres' => $clone->wheres,
        ], $boolean);
    }

    public function count(): int {
        $sql = $this->buildSQL();
        $sql = preg_replace('/SELECT(.*?)FROM/i', 'SELECT COUNT(*) as c FROM', $sql);

        $row = $this->execute($sql)->fetch(PDO::FETCH_ASSOC);
        return intval($row['c'] ?? 0);
    }

    private function execute( string $sql ) {
        $stmt = Database::pdo()->prepare($sql);
        foreach ( $this->bindings as $k => $v ) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->execute();
        return $stmt;
    }

    public function delete(): bool {
        if ( !$this->modelInstance ) {
            throw new \Exception("Delete requires loaded model");
        }

        $pk      = $this->modelInstance->primaryKey;
        $pkValue = $this->modelInstance->data[$pk] ?? null;

        if ( $pkValue === null ) {
            throw new \Exception("Delete requires ID");
        }

        $sql = "DELETE FROM {$this->table} WHERE {$pk} = :id";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->bindValue(':id', $pkValue);
        $ok = $stmt->execute();

        if ( $ok ) {
            $this->modelInstance->data = [];
        }

        return $ok;
    }

    public function selectRaw( string $sql ): self {
        $this->select[] = $sql;
        return $this;
    }

    public function updateOrInsert( array $attributes, array $values = [] ) {
        $query = new static($this->table);
        $query->attachModelClass($this->modelClass);

        foreach ( $attributes as $col => $val ) {
            $query->where($col, '=', $val);
        }

        $row = $query->first();

        if ( $row ) {
            // update
            return $query->update($values);
        }

        // insert
        $data = array_merge($attributes, $values);
        $now  = $this->date();
        if ( $this->modelInstance && $this->modelInstance->timestamps ) {
            if ( !isset($data['created_at']) ) $data['created_at'] = $now;
            if ( !isset($data['updated_at']) ) $data['updated_at'] = $now;
        }

        $cols = array_keys($data);
        $vals = array_map(fn( $c ) => ':' . $c, $cols);

        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ")
            VALUES (" . implode(',', $vals) . ")";

        $stmt = Database::pdo()->prepare($sql);

        foreach ( $data as $k => $v ) {
            $stmt->bindValue(":$k", $v);
        }

        return $stmt->execute();
    }

    public function attachModelClass( string $modelClass ) {
        $this->modelClass    = $modelClass;
        $this->modelInstance = new $modelClass();

        return $this;
    }

    public function first() {
        $this->limit(1);
        return $this->get()[0] ?? null;
    }

    public function limit( int $l ): self {
        $this->limit = $l;
        return $this;
    }

    public function get() {
        $rows = $this->execute($this->buildSQL())->fetchAll(PDO::FETCH_ASSOC);

        if ( !$this->modelClass ) return $rows;

        $models = [];
        foreach ( $rows as $r ) {
            $inst       = new $this->modelClass();
            $inst->data = $r;
            $models[]   = $inst;
        }

        if ( !empty($this->with) ) {
            $this->eagerLoad($models);
        }

        return $models;
    }

    private function eagerLoad( array &$models ) {
        if ( empty($models) ) return;

        foreach ( $this->with as $relationPath ) {
            $this->loadRelationPath($models, $relationPath);
        }
    }

    private function loadRelationPath( &$models, string $path ) {
        $parts = explode('.', $path);
        $this->loadRelationLevel($models, $parts);
    }

    private function loadRelationLevel( &$models, array $parts ) {
        if ( empty($parts) ) return;

        $relation = array_shift($parts);

        $first = $models[0];
        if ( !method_exists($first, $relation) ) return;

        $info = $first->$relation();

        $type    = $info['type'];
        $related = $info['related'];

        $relatedInst = new $related();
        $table       = $this->getModelTable($relatedInst);

        $foreignKey = $info['foreignKey'];
        $localKey   = $info['localKey'] ?? $info['ownerKey'];

        if ( $type === 'hasMany' ) {
            $this->loadHasMany($models, $related, $table, $foreignKey, $localKey, $relation, $parts);
        }
        else {
            $this->loadBelongsTo($models, $related, $table, $foreignKey, $localKey, $relation, $parts);
        }
    }

    private function getModelTable( object $model ): string {
        if ( method_exists($model, 'getTableName') ) {
            return $model->getTableName();
        }

        if ( property_exists($model, 'table') ) {
            return $model->table;
        }

        throw new Exception("Model " . get_class($model) . " must define protected \$table()");
    }

    private function loadHasMany( &$models, $related, $table, $foreign, $local, $name, $children ) {
        $ids = array_map(fn( $m ) => $m->data[$local], $models);
        $ids = array_unique($ids);

        if ( empty($ids) ) return;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql  = "SELECT * FROM $table WHERE $foreign IN ($placeholders)";
        $stmt = Database::pdo()->prepare($sql);

        foreach ( array_values($ids) as $i => $v ) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $group = [];
        foreach ( $rows as $r ) {
            $group[$r[$foreign]][] = $r;
        }

        foreach ( $models as $m ) {
            $key            = $m->data[$local];
            $childrenModels = [];

            foreach ( $group[$key] ?? [] as $r ) {
                $obj              = new $related();
                $obj->data        = $r;
                $childrenModels[] = $obj;
            }

            $m->data[$name] = $childrenModels;

            if ( !empty($children) ) {
                $this->loadRelationLevel($childrenModels, $children);
            }
        }
    }

    private function loadBelongsTo( &$models, $related, $table, $foreign, $owner, $name, $children ) {
        $ids = array_filter(array_map(fn( $m ) => $m->data[$foreign] ?? null, $models));
        $ids = array_unique($ids);

        if ( empty($ids) ) {
            foreach ( $models as $m ) {
                $m->data[$name] = null;
            }
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql  = "SELECT * FROM $table WHERE $owner IN ($placeholders)";
        $stmt = Database::pdo()->prepare($sql);

        foreach ( array_values($ids) as $i => $v ) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ( $rows as $r ) {
            $o               = new $related();
            $o->data         = $r;
            $map[$r[$owner]] = $o;
        }

        foreach ( $models as $m ) {
            $fid            = $m->data[$foreign] ?? null;
            $m->data[$name] = $map[$fid] ?? null;

            if ( $m->data[$name] && !empty($children) ) {
                $cArr = [ $m->data[$name] ];
                $this->loadRelationLevel($cArr, $children);
            }
        }
    }

    public function update( array $values ) {
        if ( empty($this->wheres) ) {
            throw new Exception("Update requires at least one WHERE clause.");
        }

        if ( $this->modelInstance && $this->modelInstance->timestamps ) {
            $values['updated_at'] = $this->date();
        }

        // set bindings
        $setParts = [];
        foreach ( $values as $col => $val ) {
            $key        = $this->bind($val);
            $setParts[] = "$col = :$key";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);

        if ( !empty($this->wheres) ) {
            $sql .= " WHERE " . $this->compileWhereTree($this->wheres);
        }

        $stmt = Database::pdo()->prepare($sql);

        foreach ( $this->bindings as $k => $v ) {
            $stmt->bindValue(":$k", $v);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function date(): string {
        return date('Y-m-d H:i:s');
    }

    public function insert( array $data ) {
        if ( $this->modelInstance && $this->modelInstance->timestamps ) {
            $data['created_at'] = $this->date();
            $data['updated_at'] = $this->date();
        }

        $cols = array_keys($data);
        $vals = array_map(fn( $c ) => ':' . $c, $cols);

        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ")
            VALUES (" . implode(',', $vals) . ")";

        $stmt = Database::pdo()->prepare($sql);

        foreach ( $data as $k => $v ) {
            $stmt->bindValue(":$k", $v);
        }

        return $stmt->execute();
    }

    public function createOrUpdate( array $attributes, array $values = [] ) {
        $query = new static($this->table);
        $query->attachModelClass($this->modelClass);

        // apply where filters
        foreach ( $attributes as $col => $val ) {
            $query->where($col, $val);
        }

        // 1) try find
        $model = $query->first();

        // 2) update
        if ( $model ) {
            foreach ( $values as $k => $v ) {
                $model->data[$k] = $v;
            }
            return $model->save();
        }

        // 3) create
        $class     = $this->modelClass;
        $obj       = new $class();
        $obj->data = array_merge($attributes, $values);

        return $obj->save();
    }

    public function save() {
        $data =& $this->modelInstance->data;
        $pk   = $this->modelInstance->primaryKey;

        if ( empty($data[$pk]) || !isset($data[$pk]) ) {
            // INSERT
            if ( $this->modelInstance->timestamps ) {
                $data['created_at'] = $this->date();
                $data['updated_at'] = $this->date();
            }

            $cols = array_keys($data);
            $vals = array_map(fn( $c ) => ':' . $c, $cols);

            $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ")
                VALUES (" . implode(',', $vals) . ")";

            $stmt = Database::pdo()->prepare($sql);
            foreach ( $data as $k => $v ) {
                $stmt->bindValue(":$k", $v);
            }
            $stmt->execute();

            // ⚠️ کلید این خط است:
            $insertedId = Database::pdo()->lastInsertId();
            $data[$pk]  = $insertedId;  // id را در data تنظیم کن

            return $this->fresh($insertedId);
        }

        // UPDATE
        if ( $this->modelInstance->timestamps ) {
            $data['updated_at'] = $this->date();
        }

        $setParts = [];
        foreach ( $data as $k => $v ) {
            if ( $k === $pk ) continue;
            $setParts[] = "$k = :$k";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE {$pk} = :{$pk}";

        $stmt = Database::pdo()->prepare($sql);
        foreach ( $data as $k => $v ) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->execute();

        return $this->fresh($data[$pk]);
    }

    public function fresh( $id ) {
        $sql  = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->bindValue(":id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ( !$row ) return null;

        $obj       = new $this->modelClass();
        $obj->data = $row;
        return $obj;
    }

    public function search( string $column, string $keyword, string $boolean = 'and' ): self {
        return $this->whereLike($column, $keyword, $boolean);
    }

    public function whereLike( string $column, string $keyword ): self {
        $param = $this->bind('%' . $keyword . '%');

        return $this->addWhere([
            'type'     => 'basic',
            'column'   => $column,
            'operator' => 'LIKE',
            'value'    => $param,
        ], 'and');
    }

    public function searchMulti( array $columns, string $keyword, string $boolean = 'and' ): self {
        return $this->whereGroup(function ( $q ) use ( $columns, $keyword, $boolean ) {
            foreach ( $columns as $c ) {
                $q->orWhereLike($c, $keyword);
            }
        }, $boolean);
    }

    public function orWhereLike( string $column, string $keyword ): self {
        $param = $this->bind('%' . $keyword . '%');

        return $this->addWhere([
            'type'     => 'basic',
            'column'   => $column,
            'operator' => 'LIKE',
            'value'    => $param,
        ], 'or');
    }

    public function orWhere( $col, $op = null, $val = null ): self {
        if ( func_num_args() === 2 ) {
            $val = $op;
            $op  = '=';
        }

        return $this->where($col, $op, $val, 'or');
    }

    public function updateOrCreate( array $attributes, array $values = [] ) {
        $query = new static($this->table);
        $query->attachModelClass($this->modelClass);

        foreach ( $attributes as $col => $val ) {
            $query->where($col, $val);
        }

        $model = $query->first();

        if ( $model ) {
            foreach ( $values as $k => $v ) {
                $model->data[$k] = $v;
            }
            return $model->save();
        }

        $class   = $this->modelClass;
        $m       = new $class();
        $m->data = array_merge($attributes, $values);
        return $m->save();
    }

    public function firstOrCreate( array $attributes, array $values = [] ) {
        $model = $this->firstOrNew($attributes, $values);

        if ( !isset($model->data['id']) ) {
            return $model->save();
        }

        return $model;
    }

    public function firstOrNew( array $attributes, array $values = [] ) {
        $query = new static($this->table);
        $query->attachModelClass($this->modelClass);

        foreach ( $attributes as $col => $val ) {
            $query->where($col, $val);
        }

        $model = $query->first();

        if ( $model ) {
            $model->is_created = false;
            return $model;
        }

        $class   = $this->modelClass;
        $m       = new $class();
        $m->data = array_merge($attributes, $values);

        $m->save();
        $m->is_created = true;

        return $m;
    }

    public function increment( string $column, int $amount = 1 ) {
        return $this->increaseColumn($column, $amount);
    }

    private function increaseColumn( string $column, int $amount ) {
        if ( empty($this->wheres) ) {
            throw new Exception("Increment/Decrement requires a WHERE clause.");
        }

        $whereBindings  = $this->bindings;
        $this->bindings = [];

        foreach ( $whereBindings as $k => $v ) {
            $this->bindings[$k] = $v;
        }

        $incKey = $this->bind($amount);
        $updKey = $this->bind($this->date());

        $sql = "
        UPDATE {$this->table}
        SET $column = $column + :$incKey,
            updated_at = :$updKey
        WHERE " . $this->compileWhereTree($this->wheres);

        $stmt = Database::pdo()->prepare($sql);

        foreach ( $this->bindings as $k => $v ) {
            $stmt->bindValue(":$k", $v);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function decrement( string $column, int $amount = 1 ) {
        return $this->increaseColumn($column, - $amount);
    }
}
