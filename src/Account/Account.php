<?php
namespace Plate\PlateFramework\Account;

use Plate\PlateFramework\Authentication\Token;
use Plate\PlateFramework\Database;
use Plate\PlateFramework\Exceptions\BadRequestException;
use Plate\PlateFramework\Exceptions\ConflictException;
use Plate\PlateFramework\Exceptions\ForbiddenException;
use Plate\PlateFramework\Exceptions\InternalServerException;
use Plate\PlateFramework\Exceptions\NotFoundException;

class Account {
    public string $uuid;
    public ?string $first_name;
    public ?string $last_name;
    public string $email;
    public bool $confirmed;
    public string $password;

    private function __construct(string $uuid, ?string $first_name, ?string $last_name, string $email, bool $confirmed, string $password)
    {
        $this->uuid = $uuid;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->confirmed = $confirmed;
        $this->password = $password;
    }

    /**
     * Creates a new account.
     * @param Database $database
     * @param string|null $first_name
     * @param string|null $last_name
     * @param string $email
     * @param string $password
     * @return static
     * @throws ConflictException
     * @throws InternalServerException
     * @throws BadRequestException
     */
    public static function create(Database $database, ?string $first_name, ?string $last_name, string $email, string $password): self
    {
        if(Email::exists($database, $email)) throw new ConflictException("E-Mail already exists.");
        $uuid = uniqid();
        $password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO ".$database->databaseTableConfig["ACCOUNTS"]."(uuid, first_name, last_name, email, confirmed, password) VALUES(:uuid, :first_name, :last_name, :email, false, :password)";
        $result = $database->execute($query, ["uuid" => $uuid, "first_name" => $first_name, "last_name" => $last_name, "email" => $email, "password" => $password]);
        if($result) {
            return new self($uuid, $first_name, $last_name, $email, false, $password);
        } throw new InternalServerException("Couldn't create account.");
    }

    /**
     * Logins into an account with given credentials & returns an authentication token.
     * @param Database $database
     * @param string $email
     * @param string $password
     * @return Token
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerException
     */
    public static function login(Database $database, string $email, string $password): Token
    {
        if(!Email::exists($database, $email)) throw new ConflictException("Account doesn't exists.");
        $query = "SELECT * FROM".$database->databaseTableConfig["ACCOUNTS"]." WHERE email = :email";
        $result = $database->fetch($query, ["email" => $email]);
        if($result) {
            if(password_verify($password, $result->password)) {
                return Token::create($database, $result->uuid);
            } throw new ForbiddenException("Incorrect password.");
        } throw new InternalServerException("Couldn't login.");
    }

    /**
     * Fetches an account by a given UUID.
     * @param Database $database
     * @param string $uuid
     * @return static
     * @throws NotFoundException
     */
    public static function fetch(Database $database, string $uuid): self
    {
        if($result = $database->fetch("SELECT * FROM ".$database->databaseTableConfig["ACCOUNTS"]." WHERE uuid = :uuid", ["uuid" => $uuid])) {
            return new self($result->uuid, $result->first_name, $result->last_name, $result->email, $result->confirmed, $result->password);
        } throw new NotFoundException("Account doesn't exist.");
    }
}