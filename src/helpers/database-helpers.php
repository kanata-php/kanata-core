<?php

if (! function_exists('mysql_table_exists')) {
    /**
     * Verify if the mysql db table exists.
     *
     * @param string $schema
     * @param string $table
     * @return bool
     */
    function mysql_table_exists(string $schema, string $table): bool
    {
        $sql = 'SELECT EXISTS (SELECT TABLE_NAME FROM information_schema.TABLES '
            .'WHERE TABLE_SCHEMA LIKE "' . $schema . '" '
            .'AND TABLE_TYPE LIKE "BASE TABLE" '
            .'AND TABLE_NAME = "' . $table . '" ) "exists";';
        $results = container()->db->getConnection('default')->select($sql);
        return isset($results[0]) && $results[0]?->exists === 1;
    }
}
