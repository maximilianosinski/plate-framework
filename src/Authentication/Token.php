<?php
namespace Plate\PlateFramework\Authentication;

use Exception;
use Plate\PlateFramework\Database;
use Plate\PlateFramework\Exceptions\UnauthorizedException;

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
     * @throws Exception
     */
    public static function create(Database $database, string $value): self|bool
    {
        $token_string = bin2hex(random_bytes(48));
        $query = "INSERT INTO ".$database->databaseTableConfig->tableKeys["AUTH_TOKENS"]."(token, value, expires) VALUES(:token, :value, NOW() + INTERVAL 1 DAY)";
        if($database->execute($query, ["token" => $token_string, "value" => $value])) {
            return new self($database, $token_string, $value, time() + 86400);
        } return false;
    }

    /**
     * Fetches an authentication token.
     * @param Database $database
     * @param string $token
     * @return static
     * @throws UnauthorizedException
     */
    public static function fetch(Database $database, string $token): self
    {
        $query = "SELECT * FROM ".$database->databaseTableConfig->tableKeys["AUTH_TOKENS"]." WHERE token = :token";
        if($result = $database->fetch($query, ["token" => $token])) {
            return new self($database, $token, $result->value, strtotime($result->expires));
        } throw new UnauthorizedException("Invalid Authentication Token.");
    }

    /**
     * Refreshes an authentication token.
     * @return bool
     * @throws UnauthorizedException
     * @throws Exception
     */
    public function refresh(): bool
    {
        $token = self::fetch($this->database, $this->token);
        self::delete();
        $token = self::create($token->database, $token->value);
        $this->token = $token->token;
        $this->value = $token->value;
        $this->expires = $token->expires;
        return true;
    }

    /**
     * Deletes the current authentication token.
     * @return bool
     */
    public function delete(): bool
    {
        $this->token = null;
        $this->value = null;
        $this->expires = 0;
        return $this->database->execute("DELETE FROM ".$this->database->databaseTableConfig->tableKeys["AUTH_TOKENS"]." WHERE token = :token");
    }

    /**
     * Validates the given authentication token.
     * @param Database $database
     * @param string $token
     * @return string Returns on success the value of the token.
     * @throws UnauthorizedException
     */
    public static function validate(Database $database, string $token): string
    {
        $token_object = self::fetch($database, $token);
        if(time() < $token_object->expires) {
            return $token_object->value;
        } throw new UnauthorizedException("Invalid Authentication Token.");
    }
}