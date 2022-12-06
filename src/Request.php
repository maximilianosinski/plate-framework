<?php
namespace Plate\PlateFramework;

class Request {
    public string $ip;
    public ?string $useragent;
    public string $method;
    public ?array $headers;
    public ?AuthenticationHeader $authenticationHeader;

    private function __construct(string $ip, ?string $useragent, string $method, ?array $headers, ?AuthenticationHeader $authenticationHeader = null)
    {
        $this->ip = $ip;
        $this->useragent = $useragent;
        $this->method = $method;
        $this->headers = $headers;
        $this->authenticationHeader = $authenticationHeader;
    }

    /**
     * Returns the current request.
     * @return Request
     */
    public static function current(): Request
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        $useragent = $_SERVER["HTTP_USER_AGENT"];
        $method = $_SERVER["REQUEST_METHOD"];
        $headers = getallheaders();
        if(array_key_exists("authorization", array_change_key_case($headers))) {
            $authHeaderType = "authorization";
        } else if(array_key_exists("authentication", array_change_key_case($headers))) {
            $authHeaderType = "authentication";
        }
        $authenticationHeaders = null;
        if(!empty($authHeaderType)) {
            $authenticationHeaders = AuthenticationHeader::current($authHeaderType);
        }
        return new Request($ip, $useragent, $method, $headers, $authenticationHeaders);
    }
}