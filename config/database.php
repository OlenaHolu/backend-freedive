<?php

use Illuminate\Support\Str;

return [

    'default' => env('DB_CONNECTION', 'pgsql'), // ✅ Cambiar a 'pgsql' para Railway

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => parse_url(env('DATABASE_URL'), PHP_URL_HOST) ?: env('DB_HOST', '127.0.0.1'),
            'port' => parse_url(env('DATABASE_URL'), PHP_URL_PORT) ?: env('DB_PORT', '3306'),
            'database' => ltrim(parse_url(env('DATABASE_URL'), PHP_URL_PATH), '/') ?: env('DB_DATABASE', 'forge'),
            'username' => parse_url(env('DATABASE_URL'), PHP_URL_USER) ?: env('DB_USERNAME', 'forge'),
            'password' => parse_url(env('DATABASE_URL'), PHP_URL_PASS) ?: env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => parse_url(env('DATABASE_URL'), PHP_URL_HOST) ?: '127.0.0.1',
            'port' => parse_url(env('DATABASE_URL'), PHP_URL_PORT) ?: '5432',
            'database' => ltrim(parse_url(env('DATABASE_URL'), PHP_URL_PATH), '/') ?: 'forge',
            'username' => parse_url(env('DATABASE_URL'), PHP_URL_USER) ?: 'forge',
            'password' => parse_url(env('DATABASE_URL'), PHP_URL_PASS) ?: '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'), // ✅ Agregar soporte para SSL
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => parse_url(env('DATABASE_URL'), PHP_URL_HOST) ?: env('DB_HOST', 'localhost'),
            'port' => parse_url(env('DATABASE_URL'), PHP_URL_PORT) ?: env('DB_PORT', '1433'),
            'database' => ltrim(parse_url(env('DATABASE_URL'), PHP_URL_PATH), '/') ?: env('DB_DATABASE', 'forge'),
            'username' => parse_url(env('DATABASE_URL'), PHP_URL_USER) ?: env('DB_USERNAME', 'forge'),
            'password' => parse_url(env('DATABASE_URL'), PHP_URL_PASS) ?: env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    'migrations' => 'migrations',

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => parse_url(env('REDIS_URL'), PHP_URL_HOST) ?: env('REDIS_HOST', '127.0.0.1'),
            'username' => parse_url(env('REDIS_URL'), PHP_URL_USER) ?: env('REDIS_USERNAME'),
            'password' => parse_url(env('REDIS_URL'), PHP_URL_PASS) ?: env('REDIS_PASSWORD'),
            'port' => parse_url(env('REDIS_URL'), PHP_URL_PORT) ?: env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => parse_url(env('REDIS_URL'), PHP_URL_HOST) ?: env('REDIS_HOST', '127.0.0.1'),
            'username' => parse_url(env('REDIS_URL'), PHP_URL_USER) ?: env('REDIS_USERNAME'),
            'password' => parse_url(env('REDIS_URL'), PHP_URL_PASS) ?: env('REDIS_PASSWORD'),
            'port' => parse_url(env('REDIS_URL'), PHP_URL_PORT) ?: env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
