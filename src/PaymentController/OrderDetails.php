<?php
namespace PlatePHP\PlateFramework\PaymentController;

class OrderDetails {
    public string $link;
    public string $order_token;
    public function __construct(string $link, string $order_token)
    {
        $this->link = $link;
        $this->order_token = $order_token;
    }
}