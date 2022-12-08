<?php
namespace Plate\PlateFramework;

class Database {
    private \PDO $pdo;
    public DatabaseTableConfig $databaseTableConfig;
    private function __construct(DatabaseTableConfig $databaseTableConfig, \PDO $pdo)
    {
        $this->databaseTableConfig = $databaseTableConfig;
        $this->pdo = $pdo;
    }

    /**
     * Returns a database object.
     * @param DatabaseTableConfig $databaseTableConfig
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @return static
     */
    public static function connect(DatabaseTableConfig $databaseTableConfig, string $host, string $database, string $username, string $password = ""): self
    {
        $dsn = "mysql:host=$host;dbname=$database";
        $pdo = new \PDO($dsn, $username, $password);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        $pdo->setAttribute(\PDO::ERRMODE_EXCEPTION, true);
        $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        return new self($databaseTableConfig, $pdo);
    }

    public function execute(string $query, ?array $parameters = array()): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($parameters);
    }
    public function fetch(string $query, ?array $parameters = array()): mixed
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);
        return $statement->fetch();
    }
    public function fetchAll(string $query, ?array $parameters = array()): array|bool
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}