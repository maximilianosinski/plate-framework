<?php

namespace Plate\PlateFramework;

class PDO {

    public static function connect(string $host, string $database, string $username, string $password = ""): \PDO
    {
        $dsn = "mysql:host=$host;dbname=$database";
        $pdo = new \PDO($dsn, $username, $password);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        $pdo->setAttribute(\PDO::ERRMODE_EXCEPTION, true);
        $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        return $pdo;
    }
}