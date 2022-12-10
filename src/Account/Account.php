<?php
namespace Plate\PlateFramework\Account;

use Plate\PlateFramework\Authentication\Token;
use Plate\PlateFramework\Database;
use Plate\PlateFramework\Exceptions\BadRequestException;
use Plate\PlateFramework\Exceptions\ConflictException;
use Plate\PlateFramework\Exceptions\ForbiddenException;
use Plate\PlateFramework\Exceptions\InternalServerException;
use Plate\PlateFramework\Exceptions\NotFoundException;
use Plate\PlateFramework\MailClient;

class Account {

    private Database $database;
    public Details $details;
    public object $data;

    private function __construct(Database $database, Details $details, object $data)
    {
        $this->database = $database;
        $this->details = $details;
        $this->data = $data;
    }

    /**
     * Creates a new account.
     * @param Database $database
     * @param string|null $first_name
     * @param string|null $last_name
     * @param string $email
     * @param string $password
     * @return static
     * @throws BadRequestException
     * @throws ConflictException
     * @throws InternalServerException
     * @throws NotFoundException
     */
    public static function create(Database $database, ?string $first_name, ?string $last_name, string $email, string $password): self
    {
        if(Email::exists($database, $email)) throw new ConflictException("E-Mail already exists.");
        if(!strlen($password) >= 8) throw new BadRequestException("Password is too short.");
        $uuid = uniqid();
        $password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO ".$database->databaseTableConfig["ACCOUNTS"]."(uuid, first_name, last_name, email, confirmed, password) VALUES(:uuid, :first_name, :last_name, :email, false, :password)";
        $result = $database->execute($query, ["uuid" => $uuid, "first_name" => $first_name, "last_name" => $last_name, "email" => $email, "password" => $password]);
        if($result) {
            return self::fetch($database, $uuid);
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
            $details = Details::build($result);

            // Remove detail properties.
            unset($result->uuid);
            unset($result->first_name);
            unset($result->last_name);
            unset($result->email);
            unset($result->confirmed);
            unset($result->password);

            return new self($database, $details, $result);
        } throw new NotFoundException("Account doesn't exist.");
    }

    /**
     * Sets a property for the current account.
     * @param string $property
     * @param mixed $value
     * @return bool
     * @throws BadRequestException
     * @throws InternalServerException
     */
    public function setProperty(string $property, mixed $value): bool
    {
        if(!property_exists(Details::class, $property)) {
            if(!empty($value)) {
                $query = "UPDATE ".$this->database->databaseTableConfig["ACCOUNTS"]." SET $property = :value WHERE uuid = :uuid";
                $result = $this->database->execute($query, ["value" => $value, "uuid" => $this->details->uuid]);
                if($result) {
                    $this->data[$property] = $value;
                    return true;
                } throw new InternalServerException("Couldn't set property.");
            } throw new BadRequestException("Can't set property with empty value.");
        } throw new BadRequestException("Can't access detail properties.");
    }

    /**
     * Unsets a property for the current account.
     * @param string $property
     * @return bool
     * @throws BadRequestException
     * @throws InternalServerException
     */
    public function unsetProperty(string $property): bool
    {
        if(!property_exists(Details::class, $property)) {
            $query = "UPDATE ".$this->database->databaseTableConfig["ACCOUNTS"]." SET $property = null WHERE uuid = :uuid";
            $result = $this->database->execute($query, ["uuid" => $this->details->uuid]);
            if($result) {
                unset($this->data[$property]);
                return true;
            } throw new InternalServerException("Couldn't unset property.");
        } throw new BadRequestException("Can't access detail properties.");
    }

    /**
     * Sets the email for the current account.
     * @param string $email
     * @return bool
     * @throws BadRequestException
     * @throws ConflictException
     * @throws InternalServerException
     */
    public function setEmail(string $email): bool
    {
        if(!Email::exists($this->database, $email)) throw new ConflictException("E-Mail already exists.");
        $query = "UPDATE ".$this->database->databaseTableConfig["ACCOUNTS"]." SET email = :email WHERE uuid = :uuid";
        $result = $this->database->execute($query, ["email" => $email, "uuid" => $this->details->uuid]);
        if($result) {
            $this->details->email = $email;
            return true;
        } throw new InternalServerException("Couldn't set email.");
    }

    /**
     * Sets the password for the current account.
     * @param string $password
     * @return bool
     * @throws BadRequestException
     * @throws InternalServerException
     */
    public function setPassword(string $password): bool
    {
        if(!strlen($password) >= 8) throw new BadRequestException("Password is too short.");
        $password = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE ".$this->database->databaseTableConfig["ACCOUNTS"]." SET password = :email WHERE uuid = :uuid";
        $result = $this->database->execute($query, ["password" => $password, "uuid" => $this->details->uuid]);
        if($result) {
            $this->details->password = $password;
            return true;
        } throw new InternalServerException("Couldn't set password.");
    }

    /**
     * Confirms the current email address.
     * @param MailClient|null $mailClient
     * @param int|null $code
     * @return bool
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerException
     * @throws NotFoundException
     */
    public function confirm(?MailClient $mailClient, ?int $code): bool
    {
        if($this->details->confirmed) throw new ConflictException("E-Mail already confirmed.");
        if(empty($code)) {
            $query = "DELETE FROM ".$this->database->databaseTableConfig["MAIL_VERIFICATION"]." WHERE email : email";
            $this->database->execute($query, ["email" => $this->details->email]);
            if(empty($mailClient)) throw new InternalServerException("No mail client specified.");
            $confirmation_code = rand(100000, 999999);
            $query = "INSERT INTO ".$this->database->databaseTableConfig["MAIL_VERIFICATION"]."(email, code) VALUES(:email, :code)";
            $result = $this->database->execute($query, ["email" => $this->details->email, "code" => $confirmation_code]);
            if($result) {
                $mailBody = "";
                if(!empty($this->details->first_name) && !empty($this->details->last_name)) {
                    $mailBody .= "Hello ".$this->details->first_name." ".$this->details->last_name.",\n";
                }
                $mailBody .= "<p>To confirm your email address enter the following code: <strong style='font-size: large'>$confirmation_code</strong></p>.";
                return $mailClient->sendMail($this->details->email, "Confirm your E-Mail address.", $mailBody);
            } throw new InternalServerException("Couldn't confirm email.");
        }

        $query = "SELECT * FROM ".$this->database->databaseTableConfig["MAIL_VERIFICATION"]." WHERE email = :email";
        $result = $this->database->fetch($query, ["email" => $this->details->email]);
        if($result) {
            if($code == $result->code) {
                $query = "DELETE FROM ".$this->database->databaseTableConfig["MAIL_VERIFICATION"]." WHERE email : email";
                $this->database->execute($query, ["email" => $this->details->email]);

                $query = "UPDATE ".$this->database->databaseTableConfig["ACCOUNTS"]." SET confirmed = true WHERE uuid = :uuid";
                $result = $this->database->execute($query, ["uuid" => $this->details->uuid]);
                if($result) {
                    $this->details->confirmed = true;
                    return true;
                } throw new InternalServerException("Couldn't confirm email address.");
            } throw new ForbiddenException("Invalid confirmation code.");
        } throw new NotFoundException("No valid confirmation code found.");
    }
}