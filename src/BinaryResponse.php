<?php
namespace Plate\PlateFramework;

class BinaryResponse {

    public mixed $data;
    public string $contentType;
    public function __construct(mixed $data, string $contentType)
    {
        $this->data = $data;
        $this->contentType = $contentType;
    }

    public function echo(int $status = 200): void
    {
        header("HTTP/1.1 $status");
        header("Content-Type: $this->contentType");
        echo $this->data;
    }
}