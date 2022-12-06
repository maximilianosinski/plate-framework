<?php
namespace Plate\PlateFramework;

class Session {

    /**
     * Sets the headers of the session.
     * @param array $headers
     * @return void
     */
    public static function setHeaders(array $headers = array()): void
    {
        foreach($headers as $key) {
            header("$key: ".$headers[$key]);
        }
    }
}