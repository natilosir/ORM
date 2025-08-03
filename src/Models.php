<?php

namespace natilosir\orm;

/**
 * @method static \natilosir\orm\DB table( string $table )
 * @method static \natilosir\orm\DB select( array|string $columns )
 * @method static \natilosir\orm\DB where( array|string $column, string|null $operator = null, mixed $value = null, string $type = 'AND' )
 * @method static \natilosir\orm\DB whereNotNull( string $column, string $type = 'AND' )
 * @method static \natilosir\orm\DB whereNull( string $column, string $type = 'AND' )
 * @method static \natilosir\orm\DB orderBy( string $column, string $direction )
 * @method static \natilosir\orm\DB limit( int $limit )
 * @method static mixed get()
 * @method static mixed first()
 * @method static int count()
 * @method static bool insert( array $data )
 * @method static bool update( array $params, array $data = null )
 * @method static bool delete( mixed $params = null )
 * @method static \natilosir\orm\DB createOrFirst( array $conditions, array $data = [] )
 * @method static \natilosir\orm\DB createOrUpdate( array $conditions, array $data = [] )
 * @method static \natilosir\orm\DB updateOrInsert( array $conditions, array $data )
 * @method static string SQL()
 * @method static \natilosir\orm\DB distinct()
 * @method static \natilosir\orm\DB search( array $conditions )
 * @method static array query( string $sql )
 */
abstract class Models {
    protected static string $table;

    public static function __callStatic( string $method, array $arguments ) {
        return DB::table(static::$table)->$method(...$arguments);
    }
}