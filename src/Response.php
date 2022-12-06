<?php
namespace Plate\PlateFramework;

class Response {
    public int $status;
    public int $code;
    public string $message;
    public mixed $data;

    /**
     * Initializes a new Response object.
     * @param int $status The HTTP Status Code
     * @param int $code
     * @param string $message
     * @param mixed $data The result of the targeted endpoint or function.
     */
    public function __construct(int $status, int $code, string $message, mixed $data = array())
    {
        $this->status = $status;
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Gives a Pretty-Printed JSON response.
     * @return void
     */
    public function json(): void
    {
        header("Content-Type: application/json");
        header("HTTP/1.1 $this->status");
        echo(json_encode(array
        (
            "code" => $this->code,
            "message" => $this->message,
            "data" => $this->data
        ), JSON_PRETTY_PRINT || JSON_NUMERIC_CHECK));
    }
}