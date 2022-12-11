<?php
namespace Plate\PlateFramework\Account;

class Details {

    public string $uuid;
    public ?string $first_name;
    public ?string $last_name;
    public string $email;
    public bool $confirmed;
    public string $password;
    public array $hosts;

    private function __construct(string $uuid, ?string $first_name, ?string $last_name, string $email, bool $confirmed, string $password, array $hosts)
    {
        $this->uuid = $uuid;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->confirmed = $confirmed;
        $this->password = $password;
        $this->hosts = $hosts;
    }

    public static function build(object $information): self
    {
        return new Details($information->uuid, $information->first_name, $information->last_name, $information->email, $information->confirmed, $information->password, json_decode($information->hosts, true));
    }
}