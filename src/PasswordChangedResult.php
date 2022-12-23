<?php
namespace PlatePHP\PlateFramework;

class PasswordChangedResult {
    public bool $changed;
    public bool $sent;

    public function __construct(bool $changed, bool $sent)
    {
        $this->changed = $changed;
        $this->sent = $sent;
    }
}