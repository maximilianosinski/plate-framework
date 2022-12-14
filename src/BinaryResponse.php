<?php
namespace PlatePHP\PlateFramework;

class BinaryResponse {

    public mixed $data;
    public string $contentType;
    public function __construct(mixed $data, string $contentType)
    {
        $this->data = $data;
        $this->contentType = $contentType;
    }

    /**
     * Prints out the given binary data.
     * @param int $status
     * @return void
     */
    public function echo(int $status = 200): void
    {
        header("HTTP/1.1 $status");
        header("Content-Type: $this->contentType");
        echo $this->data;
    }
}