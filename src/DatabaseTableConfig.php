<?php
namespace Plate\PlateFramework;

class DatabaseTableConfig {

    public array $tableKeys = array
    (
        "AUTH_TOKENS" => "authentication_tokens",
        "ACCOUNTS" => "accounts",
        "MAIL_VERIFICATION" => "mail_verification"
    );

    public function setKey(string $key, string $value): bool
    {
        $this->tableKeys[$key] = $value;
        return true;
    }
}