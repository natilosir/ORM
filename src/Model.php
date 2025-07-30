<?php

namespace natilosir\orm;

/**
 * @method static \natilosir\orm\db table( string $table )
 * @method static \natilosir\orm\db select( array|string $columns )
 * @method static \natilosir\orm\db where( array|string $column, string|null $operator = null, mixed $value = null, string $type = 'AND' )
 * @method static \natilosir\orm\db whereNotNull( string $column, string $type = 'AND' )
 * @method static \natilosir\orm\db whereNull( string $column, string $type = 'AND' )
 * @method static \natilosir\orm\db orderBy( string $column, string $direction )
 * @method static \natilosir\orm\db limit( int $limit )
 * @method static mixed get()
 * @method static mixed first()
 * @method static int count()
 * @method static bool insert( array $data )
 * @method static bool update( array $params, array $data = null )
 * @method static bool delete( mixed $params = null )
 * @method static mixed createOrFirst( array $conditions, array $data )
 * @method static mixed createOrUpdate( array $conditions, array $data )
 * @method static mixed updateOrInsert( array $conditions, array $data )
 * @method static mixed SQL()
 */
abstract class Model {
    protected static string $table;

    public static function __callStatic( string $method, array $arguments ) {
        return db::table(static::$table)->$method(...$arguments);
    }
}