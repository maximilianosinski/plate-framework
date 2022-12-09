<?php
namespace Plate\PlateFramework;

class DatabasePropertyConfig {

    public array $accountPropertyKeys = array
    (
        "FIRST_NAME" => "first_name",
        "LAST_NAME" => "last_name",
        "EMAIL" => "email",
        "PASSWORD" => "password"
    );

    public function setAccountPropertyKey(string $key, string $value): bool
    {
        $this->accountPropertyKeys[$key] = $value;
        return true;
    }

    public function unsetAccountPropertyKey(string $key): bool
    {
        if(array_key_exists($key, $this->accountPropertyKeys)) {
            unset($this->accountPropertyKeys[$key]);
            return true;
        } return false;
    }
}