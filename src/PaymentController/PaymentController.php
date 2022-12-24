<?php
namespace PlatePHP\PlateFramework\PaymentController;

use PlatePHP\PlateFramework\Exceptions\BadRequestException;
use PlatePHP\PlateFramework\Exceptions\InternalServerException;

class PaymentController {
    private string $token;
    private string $environment;
    private function __construct(string $token, string $environment)
    {
        $this->token = $token;
        $this->environment = $environment;
    }

    /**
     * Builds a new PaymentController.
     * @param bool $production
     * @param string $id
     * @param string $secret
     * @return static
     * @throws BadRequestException
     */
    public static function build(bool $production, string $id, string $secret): self
    {
        $environment = ".sandbox";
        if($production) {
            $environment = "";
        }
        $url = "https://api-m$environment.paypal.com/v1/oauth2/token";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array
        (
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic ".base64_encode("$id:$secret")
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $data = "grant_type=client_credentials";
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $resp = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($resp, true);
        if(!empty($json["access_token"])) {
            return new self($json["access_token"], $environment);
        } throw new BadRequestException("Authentication Request to PayPal API failed.");
    }

    /**
     * Returns a PayPal Order by a Order Token.
     * @param string $order_token
     * @return OrderResponse
     */
    public function getOrder(string $order_token): OrderResponse
    {
        return $this->orderRequest($order_token, false);
    }

    /**
     * Captures a PayPal Order by an Order Token.
     * @param string $order_token
     * @return OrderResponse
     */
    public function captureOrder(string $order_token): OrderResponse
    {
        return $this->orderRequest($order_token, true);
    }
    private function orderRequest(string $order_token, bool $capture): OrderResponse
    {
        $trailing = "";
        if($capture) {
            $trailing = "/capture";
        }
        $url = "https://api-m$this->environment.paypal.com/v2/checkout/orders/$order_token$trailing";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array
        (
            "Content-Type: application/json",
            "Authorization: Bearer $this->token"
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if($capture) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, array());
        }
        $resp = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($resp, true);
        return new OrderResponse($json["status"], $json["purchase_units"], $json);
    }

    /**
     * Creates an Order with a given OrderBodies Array.
     * @param array $order_bodies
     * @param string $return_referer
     * @param string $cancel_referer
     * @param string|null $brand_name
     * @return OrderDetails
     * @throws InternalServerException
     */
    public function createOrder(array $order_bodies, string $return_referer, string $cancel_referer, ?string $brand_name = null): OrderDetails
    {
        $purchase_units = array();
        foreach($order_bodies as $order_body) {
            if(!$order_body instanceof OrderBody) {
                throw new InternalServerException("Array contains invalid element. Must be instance of OrderBody.");
            }
            $purchase_units[] = array
            (
                "reference_id" => $order_body->reference_id,
                "amount" => array
                (
                    "currency_code" => $order_body->currency_code,
                    "value" => $order_body->amount
                )
            );
        }
        $body = array
        (
            "intent" => "CAPTURE",
            "application_context" => array
            (
                "cancel_url" => $cancel_referer,
                "return_url" => $return_referer
            ),
            "purchase_units" => $purchase_units
        );
        if(!empty($brand_name)) {
            $body["application_context"]["brand_name"] = $brand_name;
        }

        $url = "https://api-m$this->environment.paypal.com/v2/checkout/orders";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, $url);
        $headers = array
        (
            "Content-Type: application/json",
            "Authorization: Bearer $this->token"
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_PRETTY_PRINT));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($resp, true);
        return new OrderDetails($json["links"][1]["href"], $json["id"]);
    }
}