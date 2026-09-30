<?php

namespace App\Database;

use Illuminate\Database\Schema\Grammars\MariaDbGrammar as BaseMariaDbGrammar;
use Illuminate\Support\Fluent;

class MariaDbGrammar extends BaseMariaDbGrammar
{
    /**
     * Create the column definition for a json type (ensures full compatibility with MariaDB 10.1+).
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'longtext';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'longtext';
    }

    /**
     * Compile the query to determine the list of columns (supports MariaDB 10.1 without generation_expression).
     *
     * @param  string|null  $schema
     * @param  string  $table
     * @return string
     */
    public function compileColumns($schema, $table)
    {
        return sprintf(
            'select column_name as `name`, data_type as `type_name`, column_type as `type`, '
            .'collation_name as `collation`, is_nullable as `nullable`, '
            .'column_default as `default`, column_comment as `comment`, '
            .'null as `expression`, extra as `extra` '
            .'from information_schema.columns where table_schema = %s and table_name = %s '
            .'order by ordinal_position asc',
            $schema ? $this->quoteString($schema) : 'schema()',
            $this->quoteString($table)
        );
    }
}
