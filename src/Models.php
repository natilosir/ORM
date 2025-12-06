<?php

namespace natilosir\orm;

use natilosir\bot\Log;

abstract class Models {
    public array  $data       = [];
    public string $table;
    public bool   $timestamps = false;
    public string $primaryKey = 'id';

    public static function select( $columns ): DB {
        return static::query()->select($columns);
    }

    public static function query(): DB {
        return ( new DB(static::tableName()) )->attachModelClass(static::class);
    }

    protected static function tableName(): string {
        $m = new static();
        return $m->getTableName();
    }

    protected function getTableName(): string {
        if ( !isset($this->table) ) {
            throw new \Exception("Model " . static::class . " must define protected string \$table");
        }
        return $this->table;
    }

    public static function with( ...$relations ): DB {
        return static::query()->with(...$relations);
    }

    public static function orWhere( ...$args ): DB {
        return static::query()->orWhere(...$args);
    }

    public static function whereNotNull( string $column ) {
        return static::query()->whereNotNull($column);
    }

    public static function orWhereNotNull( string $column ) {
        return static::query()->orWhereNotNull($column);
    }

    public static function whereIn( string $column, array $values ): DB {
        return static::query()->whereIn($column, $values);
    }

    public static function whereNotIn( string $column, array $values ): DB {
        return static::query()->whereNotIn($column, $values);
    }

    public static function whereBetween( string $column, array $values ): DB {
        return static::query()->whereBetween($column, $values);
    }

    public static function whereNotBetween( string $column, array $values ): DB {
        return static::query()->whereNotBetween($column, $values);
    }

    public static function whereDate( string $column, string $operator, $value ): DB {
        return static::query()->whereDate($column, $operator, $value);
    }

    public static function whereMonth( string $column, string $operator, $value ): DB {
        return static::query()->whereMonth($column, $operator, $value);
    }

    public static function whereYear( string $column, string $operator, $value ): DB {
        return static::query()->whereYear($column, $operator, $value);
    }

    public static function whereDay( string $column, string $operator, $value ): DB {
        return static::query()->whereDay($column, $operator, $value);
    }

    public static function whereRaw( string $sql, array $bindings = [] ): DB {
        return static::query()->whereRaw($sql, $bindings);
    }

    public static function orderBy( string $column, string $direction = 'ASC' ): DB {
        return static::query()->orderBy($column, $direction);
    }

    public static function limit( int $limit ): DB {
        return static::query()->limit($limit);
    }

    public static function get() {
        return static::query()->get();
    }

    public static function findOrFail( $id ) {
        $model = static::find($id);
        if ( !$model ) {
            throw new \Exception(static::class . " not found with ID " . $id);
        }
        return $model;
    }

    public static function find( $id ) {
        return static::query()->where('id', $id)->first();
    }

    public static function first() {
        return static::query()->first();
    }

    public static function where( ...$args ): DB {
        return static::query()->where(...$args);
    }

    public static function count(): int {
        return static::query()->count();
    }

    public static function distinct(): DB {
        return static::query()->distinct();
    }

    public static function updateOrInsert( array $attributes, array $values = [] ): mixed {
        return static::query()->updateOrInsert($attributes, $values);
    }

    public static function createOrUpdate( array $attributes, array $values = [] ): mixed {
        return static::query()->createOrUpdate($attributes, $values);
    }

    public static function search( string $column, string $keyword ) {
        return static::query()->search($column, $keyword);
    }

    public static function searchMulti( array $columns, string $keyword ) {
        return static::query()->searchMulti($columns, $keyword);
    }

    public static function whereLike( string $column, string $keyword ): DB {
        return static::query()->whereLike($column, $keyword);
    }

    public static function orWhereLike( string $column, string $keyword ): DB {
        return static::query()->orWhereLike($column, $keyword);
    }

    public static function selectRaw( string $expression ): DB {
        return static::query()->selectRaw($expression);
    }

    public static function SQL(): string {
        return static::query()->SQL();
    }

    public static function firstOrCreate( array $conditions, array $values = [] ): static {
        return static::query()->firstOrNew($conditions, $values);
    }

    public static function firstOrNew( array $conditions, array $values = [] ): static {
        return static::query()->firstOrNew($conditions, $values);
    }

    public static function insert( array $data ) {
        return static::query()->insert($data);
    }

    public static function createOrFirst( array $conditions, array $values = [] ): static {
        return static::query()->firstOrNew($conditions, $values);
    }

    public static function updateOrCreate( array $conditions, array $values = [] ): static {
        $row = static::query()->whereArray($conditions)->first();

        if ( $row ) {
            foreach ( $values as $key => $value ) {
                $row->$key = $value;
            }
            $row->save();
            $row->is_updated = true;
            return $row;
        }

        $model = new static();
        foreach ( array_merge($conditions, $values) as $key => $value ) {
            $model->$key = $value;
        }
        $model->save();
        $model->is_created = true;
        return $model;
    }

    public function save() {
        return ( new DB($this->getTableName()) )->attachModelClass(static::class)->loadModel($this)->save();
    }

    public static function update( array $data ): mixed {
        return static::query()->update($data);
    }

    public function dd() {
        $this->lg();
        die();
    }

    public function lg() {
        $payload = [
            'model' => static::class,
            'table' => $this->table,
            'data'  => $this->data,
        ];
        Log::debug($payload);
    }

    public function log() {
        $this->lg();
    }

    public function delete(): bool {
        $pk = $this->primaryKey ?? 'id';

        if ( !isset($this->data[$pk]) ) {
            throw new \Exception("Delete requires ID");
        }

        return ( new DB($this->getTableName()) )->attachModelClass(static::class)->loadModel($this)
            ->where($pk, $this->data[$pk])->delete();
    }

    public function hasMany( string $related, string $foreignKey, string $localKey = 'id' ): array {
        return [
            'type'       => 'hasMany',
            'related'    => $related,
            'foreignKey' => $foreignKey,
            'localKey'   => $localKey,
        ];
    }

    public function belongsTo( string $related, string $foreignKey, string $ownerKey = 'id' ): array {
        return [
            'type'       => 'belongsTo',
            'related'    => $related,
            'foreignKey' => $foreignKey,
            'ownerKey'   => $ownerKey,
        ];
    }

    public function __get( string $key ) {
        return $this->data[$key] ?? null;
    }

    public function __set( string $key, $value ): void {
        $this->data[$key] = $value;
    }
}
