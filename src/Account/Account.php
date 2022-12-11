<?php
namespace Plate\PlateFramework\Account;

use Exception;
use Plate\PlateFramework\Authentication\Token;
use Plate\PlateFramework\Database;
use Plate\PlateFramework\Exceptions\BadRequestException;
use Plate\PlateFramework\Exceptions\ConflictException;
use Plate\PlateFramework\Exceptions\ForbiddenException;
use Plate\PlateFramework\Exceptions\InternalServerException;
use Plate\PlateFramework\Exceptions\NotFoundException;
use Plate\PlateFramework\Exceptions\UnauthorizedException;
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
     * @throws Exception
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
     * Clear a property for the current account.
     * @param string $property
     * @return bool
     * @throws BadRequestException
     * @throws InternalServerException
     */
    public function clearProperty(string $property): bool
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
     * Confirm the current email address.
     * @param MailClient|null $mailClient
     * @param int|null $code
     * @return bool
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerException
     * @throws NotFoundException
     */
    public function confirm(?MailClient $mailClient, ?int $code = 0): bool
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
                $mailBody .= "<p>To confirm your email address enter the following code.<br><strong style='font-size: large'>$confirmation_code</strong></p>.";
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

    /**
     * Unconfirm the current email address.
     * @return bool
     * @throws InternalServerException
     */
    public function unconfirm(): bool
    {
        $query = "UPDATE ".$this->database->databaseTableConfig["ACCOUNTS"]." SET confirmed = false WHERE uuid = :uuid";
        $result = $this->database->execute($query, ["uuid" => $this->details->uuid]);
        if($result) {
            $this->details->confirmed = false;
            return true;
        } throw new InternalServerException("Couldn't unconfirm email address.");
    }

    /**
     * Requests a change for the password of a specified account.
     * @param Database $database
     * @param MailClient|null $mailClient
     * @param string|null $referrer
     * @param string|null $token
     * @param string|null $uuid
     * @param string|null $password
     * @return bool
     * @throws BadRequestException
     * @throws InternalServerException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws Exception
     */
    public static function changePassword(Database $database, ?MailClient $mailClient, ?string $referrer, ?string $token, ?string $uuid, ?string $password): bool
    {
        if(empty($token)) {
            if(!empty($mailClient)) {
                if(!empty($uuid)) {
                    if(!empty($referrer)) {
                        $database->execute("DELETE FROM ".$database->databaseTableConfig["RESET_PASSWORD_TOKENS"]." WHERE uuid = :uuid", ["uuid" => $uuid]);

                        $token = bin2hex(random_bytes(32));
                        $query = "INSERT INTO ".$database->databaseTableConfig["RESET_PASSWORD_TOKENS"]."(uuid, token, expires) VALUES(:uuid, :token, NOW() + INTERVAL 10 MINUTE)";
                        $result = $database->execute($query, ["uuid" => $uuid, "token" => $token]);
                        if($result) {
                            $account = self::fetch($database, $uuid);
                            $mailBody = "";
                            if(!empty($account->details->first_name) && !empty($account->details->last_name)) {
                                $mailBody .= "Hello ".$account->details->first_name." ".$account->details->last_name.",\n";
                            }
                            $link = "$referrer?token=$token";
                            $mailBody .= "<p>To reset your password, click the following link.<br><a href='$link'>$link</a></p>.";
                            return $mailClient->sendMail($account->details->email, "Reset your password.", $mailBody);
                        } throw new InternalServerException("Couldn't create password reset token.");
                    } throw new BadRequestException("No Referrer given.");
                } throw new BadRequestException("No UUID specified.");
            } throw new BadRequestException("No mail client specified.");
        }
        if(!empty($password)) {
            if(strlen($password) >= 8) {
                $database->execute("DELETE FROM ".$database->databaseTableConfig["RESET_PASSWORD_TOKENS"]." WHERE NOW() > expires");
                $result = $database->fetch("SELECT * FROM ".$database->databaseTableConfig["RESET_PASSWORD_TOKENS"]." WHERE token = :token", ["token" => $token]);
                if($result) {
                    $database->execute("DELETE FROM ".$database->databaseTableConfig["RESET_PASSWORD_TOKENS"]." WHERE uuid = :uuid", ["uuid" => $uuid]);
                    $account = self::fetch($database, $result->uuid);
                    return $account->setPassword($password);
                } throw new UnauthorizedException("Invalid password reset token.");
            } throw new BadRequestException("Password is too short.");
        } throw new BadRequestException("No password given.");
    }

    /**
     * Requests a change for the email of the current account.
     * @param MailClient|null $mailClient
     * @param string|null $referrer
     * @param string|null $token
     * @param string|null $new_email
     * @return bool
     * @throws BadRequestException
     * @throws ConflictException
     * @throws InternalServerException
     * @throws UnauthorizedException
     * @throws Exception
     */
    public function changeEmail(?MailClient $mailClient, ?string $referrer, ?string $token, ?string $new_email): bool
    {
        if(empty($token)) {
            if(!empty($mailClient)) {
                if(!empty($referrer)) {
                    if(!Email::exists($this->database, $new_email)) {
                        $this->database->execute("DELETE FROM ".$this->database->databaseTableConfig["CHANGE_EMAIL_TOKENS"]." WHERE uuid = :uuid", ["uuid" => $this->details->uuid]);

                        $token = bin2hex(random_bytes(32));
                        $query = "INSERT INTO ".$this->database->databaseTableConfig["CHANGE_EMAIL_TOKENS"]."(uuid, token, new_email, expires) VALUES(:uuid, :token, :new_email, NOW() + INTERVAL 10 MINUTE)";
                        $result = $this->database->execute($query, ["uuid" => $this->details->uuid, "token" => $token, "new_email" => $new_email]);
                        if($result) {
                            $mailBody = "";
                            if(!empty($this->details->first_name) && !empty($this->details->last_name)) {
                                $mailBody .= "Hello ".$this->details->first_name." ".$this->details->last_name.",\n";
                            }
                            $link = "$referrer?token=$token";
                            $mailBody .= "<p>To change your email address to, click the following link.<br><a href='$link'>$link</a></p>.";
                            return $mailClient->sendMail($new_email, "Change your email.", $mailBody);
                        } throw new InternalServerException("Couldn't create change email token.");
                    } throw new BadRequestException("Invalid email address.");
                } throw new BadRequestException("No Referrer given.");
            } throw new BadRequestException("No mail client specified.");
        }
        $this->database->execute("DELETE FROM ".$this->database->databaseTableConfig["CHANGE_EMAIL_TOKENS"]." WHERE NOW() > expires");
        $result = $this->database->fetch("SELECT * FROM ".$this->database->databaseTableConfig["CHANGE_EMAIL_TOKENS"]." WHERE token = :token", ["token" => $token]);
        if($result) {
            $result = self::setEmail($result->new_email);
            if($result) {
                $this->database->execute("DELETE FROM ".$this->database->databaseTableConfig["CHANGE_EMAIL_TOKENS"]." WHERE uuid = :uuid", ["uuid" => $this->details->uuid]);
                return true;
            } throw new InternalServerException("Couldn't set email.");
        } throw new UnauthorizedException("Invalid change email token.");
    }
}