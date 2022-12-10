<?php
namespace Plate\PlateFramework\Account;

use Plate\PlateFramework\Database;
use Plate\PlateFramework\Exceptions\BadRequestException;
use Plate\PlateFramework\Exceptions\NotFoundException;

class Email {

    public string $uuid;
    private function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function validate(string $email): bool
    {
        if(!empty($email)) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        } return false;
    }

    /**
     * Checks if an email address exists.
     * @param Database $database
     * @param string $email
     * @return bool
     * @throws BadRequestException
     */
    public static function exists(Database $database, string $email): bool
    {
        if(!self::validate($email)) throw new BadRequestException("Invalid E-Mail address.");
        return $database->fetch("SELECT * FROM ".$database->databaseTableConfig["ACCOUNTS"]." WHERE email = :email", ["email" => $email]);
    }

    /**
     * Fetches an email address.
     * @param Database $database
     * @param string $email
     * @return static
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public static function fetch(Database $database, string $email): self
    {
        if(!self::validate($email)) throw new BadRequestException("Invalid E-Mail address.");
        if($result = $database->fetch("SELECT * FROM ".$database->databaseTableConfig["ACCOUNTS"]." WHERE email = :email", ["email" => $email])) {
            return new self($result->uuid);
        } throw new NotFoundException("E-Mail doesn't exist.");
    }
}