<?php
namespace Plate\PlateFramework;

class DatabaseTableConfig {

    public array $tableKeys = array
    (
        "AUTH_TOKENS" => "authentication_tokens",
        "ACCOUNTS" => "accounts",
        "MAIL_OPT" => "mail_opt"
    );

    public function setKey(string $key, string $value): void
    {
        $this->tableKeys[$key] = $value;
    }
}