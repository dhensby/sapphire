<?php

// Bootstrap environment variables

use Dotenv\Loader;

$loader = new Loader(null);
if (!getenv('SS_ENVIRONMENT_TYPE')) {
    $loader->setEnvironmentVariable('SS_ENVIRONMENT_TYPE', 'dev');
}

if (!getenv('SS_DATABASE_CLASS') && !getenv('SS_DATABASE_USERNAME')) {
    // The default settings let us define the database config via environment vars
    // Database connection, including PDO and legacy ORM support
    switch (getenv('DB')) {
        case "PGSQL";
            $loader->setEnvironmentVariable('SS_DATABASE_CLASS', 'pdo_pgsql');
            $loader->setEnvironmentVariable('SS_DATABASE_USERNAME', 'postgres');
            $loader->setEnvironmentVariable('SS_DATABASE_PASSWORD', '');
            break;

        case "SQLITE":
            $loader->setEnvironmentVariable('SS_DATABASE_CLASS', 'pdo_sqlite');
            $loader->setEnvironmentVariable('SS_DATABASE_USERNAME', 'root');
            $loader->setEnvironmentVariable('SS_DATABASE_PASSWORD', '');
            $loader->setEnvironmentVariable('SS_SQLITE_DATABASE_PATH', ':memory:');
            break;

        default:
            $loader->setEnvironmentVariable('SS_DATABASE_CLASS', getenv('PDO') ? 'pdo_mysql' : 'mysqli');
            $loader->setEnvironmentVariable('SS_DATABASE_USERNAME', 'root');
            $loader->setEnvironmentVariable('SS_DATABASE_PASSWORD', '');
    }

    $loader->setEnvironmentVariable('SS_DATABASE_SERVER', '127.0.0.1');
    $loader->setEnvironmentVariable('SS_DATABASE_CHOOSE_NAME', true);
}
