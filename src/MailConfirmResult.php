<?php
namespace PlatePHP\PlateFramework;

class MailConfirmResult {
    public bool $confirmed;
    public bool $sent;

    public function __construct(bool $confirmed, bool $sent)
    {
        $this->confirmed = $confirmed;
        $this->sent = $sent;
    }
}