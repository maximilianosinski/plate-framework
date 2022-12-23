<?php
namespace Plate\PlateFramework\PaymentController;

class OrderResponse {
    public string $status;
    public array $units;
    public object $body;

    public function __construct(string $status, array $units, object $body)
    {
        $this->status = $status;
        $purchase_units = array();
        foreach($units as $unit) {
            $purchase_units[] = new OrderBody($unit["amount"], $unit["currency_code"], $unit["reference_id"]);
        }
        $this->units = $purchase_units;
        $this->body = $body;
    }
}