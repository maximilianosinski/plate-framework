<?php
namespace Plate\PlateFramework;

class AuthenticationHeader {

    public string $scheme;
    public string|array $value;

    private function __construct(string $scheme, string|array $value)
    {
        $this->scheme = $scheme;
        $this->value = $value;
    }

    /**
     * Returns the current authentication or authorization header.
     * @param string $header Can be "Authentication" or "Authorization".
     * @return AuthenticationHeader|null Returns null if the Header is missing or invalid.
     */
    public static function current(string $header = "Authentication"): ?self
    {
        if(strtolower($header) == "authentication" || strtolower($header) == "authorization") {
            $headers = array_change_key_case(getallheaders());
            if(array_key_exists($header, $headers)) {
                $headerValues = explode(" ", $headers[$header]);
                if(count($headerValues) == 2) {
                    $scheme = $headerValues[0];
                    if($scheme == "Basic") {
                        $value = base64_decode($headerValues[1]);
                        if(str_contains($value, ":")) {
                            $value = explode(":", $value);
                        }
                    } else {
                        $value = $headerValues[1];
                    }
                    return new self($scheme, $value);
                }
            }
        }
        return null;
    }
}