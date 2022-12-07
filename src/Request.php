<?php
namespace Plate\PlateFramework;

class Request {
    public string $ip;
    public ?string $useragent;
    public string $method;
    public ?array $headers;
    public ?AuthenticationHeader $authentication_header;

    private function __construct(string $ip, ?string $useragent, string $method, ?array $headers, ?AuthenticationHeader $authentication_header = null)
    {
        $this->ip = $ip;
        $this->useragent = $useragent;
        $this->method = $method;
        $this->headers = $headers;
        $this->authentication_header = $authentication_header;
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
        $authentication_header = null;
        if(!empty($authHeaderType)) {
            $authentication_header = AuthenticationHeader::current($authHeaderType);
        }
        return new Request($ip, $useragent, $method, $headers, $authentication_header);
    }
}