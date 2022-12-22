<?php
namespace Plate\PlateFramework\PaymentController;

class OrderBody {
    public string $reference_id;
    public float $amount;
    public string $currency_code;

    public function __construct(float $amount, string $currency_code, ?string $reference_id = null)
    {
        $this->amount = $amount;
        $this->currency_code = $currency_code;
        if(empty($reference_id)) {
            $this->reference_id = uniqid();
        } else {
            $this->reference_id = $reference_id;
        }
    }
}