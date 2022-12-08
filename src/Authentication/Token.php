<?php
namespace Plate\PlateFramework\Authentication;

use Plate\PlateFramework\Database;
use Plate\PlateFramework\Exceptions\NotFoundException;

class Token {
    private Database $database;
    public string $token;
    public string $value;
    public int $expires;
    public function __construct(Database $database, string $token, string $value, int $expires)
    {
        $this->token = $token;
        $this->value = $value;
        $this->expires = $expires;
        $this->database = $database;
    }

    /**
     * Creates a new authentication token.
     * @param Database $database
     * @param string $value
     * @return bool|static
     */
    public static function create(Database $database, string $value): self|bool
    {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $characters_length = strlen($characters);
        $token_string = "";
        for ($i = 0; $i < 64; $i++) {
            $token_string .= $characters[rand(0, $characters_length - 1)];
        }
        $query = "INSERT INTO ".$database->databaseTableConfig["AUTH_TOKENS"]."(token, value, expires) VALUES(:token, :value, NOW() + INTERVAL 1 DAY)";
        if($database->execute($query, ["token" => $token_string, "value" => $value])) {
            return new self($database, $token_string, $value, time() + 86400);
        } return false;
    }

    /**
     * Deletes all expired authentication tokens.
     * @param Database $database
     * @return void
     */
    private static function evaluateAll(Database $database): void
    {
        $query = "DELETE FROM ".$database->databaseTableConfig["AUTH_TOKENS"]." WHERE expires < NOW()";
        $database->execute($query);
    }

    /**
     * Fetches an authentication token.
     * @param Database $database
     * @param string $token
     * @return static
     * @throws NotFoundException
     */
    public static function fetch(Database $database, string $token): self
    {
        self::evaluateAll($database);
        $query = "SELECT * FROM ".$database->databaseTableConfig["AUTH_TOKENS"]." WHERE token = :token";
        if($result = $database->fetch($query, ["token" => $token])) {
            return new self($database, $token, $result->value, strtotime($result->expires));
        } throw new NotFoundException("Authentication Token not found.");
    }

    /**
     * Deletes the current authentication token.
     * @return bool
     */
    public function delete(): bool
    {
        return $this->database->execute("DELETE FROM ".$this->database->databaseTableConfig["AUTH_TOKENS"]." WHERE token = :token");
    }

}