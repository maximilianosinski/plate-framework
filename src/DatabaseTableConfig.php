<?php
namespace Plate\PlateFramework;

class DatabaseTableConfig {

    public array $tableKeys = array
    (
        "AUTH_TOKENS" => "authentication_tokens",
        "ACCOUNTS" => "accounts",
        "MAIL_VERIFICATION" => "mail_verification",
        "RESET_PASSWORD_TOKENS" => "reset_password_tokens",
        "CHANGE_EMAIL_TOKENS" => "change_email_tokens",
        "HOST_VERIFICATION" => "host_verification"
    );

    public function setKey(string $key, string $value): bool
    {
        $this->tableKeys[$key] = $value;
        return true;
    }
}