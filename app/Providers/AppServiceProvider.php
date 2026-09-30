<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Connection::resolverFor('mariadb', function ($connection, $database, $prefix, $config) {
            $conn = new \Illuminate\Database\MariaDbConnection($connection, $database, $prefix, $config);
            $conn->setSchemaGrammar(new \App\Database\MariaDbGrammar($conn));

            return $conn;
        });

        \Illuminate\Database\Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            $conn = new \Illuminate\Database\MySqlConnection($connection, $database, $prefix, $config);
            $conn->setSchemaGrammar(new \App\Database\MariaDbGrammar($conn));

            return $conn;
        });
    }
}
