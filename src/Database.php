<?php
namespace Plate\PlateFramework;

class Database {
    private \PDO $pdo;
    private function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a database object.
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @return static
     */
    public static function connect(string $host, string $database, string $username, string $password = ""): self
    {
        $dsn = "mysql:host=$host;dbname=$database";
        $pdo = new \PDO($dsn, $username, $password);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        $pdo->setAttribute(\PDO::ERRMODE_EXCEPTION, true);
        $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        return new self($pdo);
    }

    public function execute(string $query, ?array $parameters): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($parameters);
    }
    public function fetch(string $query, ?array $parameters): mixed
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);
        return $statement->fetch();
    }
    public function fetchAll(string $query, ?array $parameters): array|bool
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}