<?php
namespace PlatePHP\PlateFramework\PaymentController;

class OrderResponse {
    public string $status;
    public array $reference_ids;
    public array $body;

    public function __construct(string $status, array $units, array $body)
    {
        $this->status = $status;
        $purchase_units = array();
        foreach($units as $unit) {
            if(!in_array($unit["reference_id"], $purchase_units)) {
                $purchase_units[] = $unit["reference_id"];
            }
        }
        $this->reference_ids = $purchase_units;
        $this->body = $body;
    }
}