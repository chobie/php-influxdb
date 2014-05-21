<?php
namespace InfluxDB\Client\Driver;

use InfluxDB\Client\Driver;
use InfluxDB\Client\Http\Request;

class CurlDriver implements Driver
{
    protected $curl;

    protected $debug = false;

    public function setDebug($bool)
    {
        $this->debug = $bool;
    }

    public function __construct()
    {
        $this->connect();
    }

    protected function connect()
    {
        $ch = curl_init();
        $this->curl = $ch;

        return $this->curl;
    }

    public function request(\InfluxDB\Client\Http\Request $request)
    {
        return $this->requestImpl($request->getRequestMethod(),
            $request->getEndpoint(),
            $request->getQuery(),
            $request->getQueryParams(),
            $request->getPostField(),
            $request
        );
    }

    public function setOption(\Closure $closure)
    {
        $closure($this->curl);
    }

    protected function requestImpl($http_method = "GET", $endpoint, $query, $params, $post_field = array(), Request $request)
    {
        $curl = $this->curl;
        if (!is_resource($curl) || curl_errno($curl)) {
            $curl = $this->connect();
        }

        curl_setopt($curl ,CURLOPT_HTTPHEADER, array());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "PhpInflux/0.1");
        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        $url = $endpoint . $query;
        if (!empty($params)) {
            $url .= "?" . http_build_query($params);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        $headers = array();
        foreach ($request->getHeaders() as $key => $value) {
            if ($key == "Content-Length") {
                /* Note: curl don't need content length as calculate itself */
                continue;
            }
            $headers[] = sprintf("%s: %s", $key, $value);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($http_method == "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getContentBody());
        } else if ($http_method == "GET") {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        } else if ($http_method == 'PUT') {
            curl_setopt($curl, CURLOPT_PUT, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getContentBody());
        } else if ($http_method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getContentBody());
        } else {
            throw new \Exception("unsupported http method: " . $http_method);
        }

        if ($this->debug) {
            echo $request->getContentBody();
        }

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);

        if (!$response) {
            throw new \Exception(sprintf("connection failed: %s", $url));
        }

        $header = substr($response, 0, $info['header_size']);
        $headers = array();
        foreach(preg_split("/\r?\n/", trim($header)) as $line) {
            if (!isset($headers['HTTP_CODE'])) {
                list($version, $status, $status) = explode(" ", $line, 3);
                $headers['HTTP_CODE'] = $status;
            } else {
                @list($key, $value) = explode(":", $line, 2);
                $headers[$key] = trim($value);
            }
        }

        if (isset($header["Connection"]) && $header["Connection"] == "close") {
            curl_close($this->curl);
            unset($this->curl);
        }

        $output = substr($response, $info['header_size']);
        if ($this->debug) {
            echo $output . PHP_EOL;
        }

        return array(
            $headers,
            $output
        );
    }
}