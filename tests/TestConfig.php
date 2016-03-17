<?php

class TestConfig
{
    const DBSM = "mysql";
    const HOST = "localhost";
    const USER = "root";
    const PASSWORD = "root";
    const DB_NAME = "scheduler";
    const TEMP_TABLE_NAME = "temp_table_20160315";
    public static $fields = array(
        "id" => "INT(11) NOT NULL",
        "start_date" => "DATETIME NOT NULL",
        "end_date" => "DATETIME NOT NULL",
        "text" => "VARCHAR(255) NOT NULL",
        "rec_type" => "VARCHAR(50) DEFAULT NULL",
        "event_length" => " BIGINT(20) DEFAULT NULL",
        "event_pid" => "INT(11) DEFAULT NULL"
    );
}