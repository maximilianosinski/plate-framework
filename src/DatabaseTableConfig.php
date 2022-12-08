<?php
namespace Plate\PlateFramework;

class DatabaseTableConfig {

    public array $tableKeys = array
    (
        "AUTH_TOKENS" => "authentication_tokens"
    );

    public function setKey(string $key, string $value): void
    {
        $this->tableKeys[$key] = $value;
    }
}