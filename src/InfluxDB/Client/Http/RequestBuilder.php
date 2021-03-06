<?php
namespace InfluxDB\Client\Http;

class RequestBuilder
{
    /** @var string $request_method */
    protected $request_method = 'GET';

    /** @var  string $endpoint */
    protected $endpoint;

    /** @var  string $query */
    protected $query;

    /** @var array $query_params */
    protected $query_params = array();

    /** @var array $post_field */
    protected $post_field = array();

    /** @var string $version HTTP version */
    protected $http_version = '1.0';

    /** @var  string $api_version */
    protected $api_version = 'v1';

    /** @var  string $user_agent */
    protected $user_agent;

    /** @var  string $proxy */
    protected $proxy;

    protected $content_type;

    public function __construct()
    {
    }

    public function setContentType($type)
    {
        $this->content_type = $type;
    }

    public function setRequestMethod($http_method)
    {
        $this->request_method = $http_method;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function setQueryParams($params)
    {
        $this->query_params = $params;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function setPostField($post_field)
    {
        $this->post_field = $post_field;
    }

    public function build()
    {
        $data = null;
        $headers = array();

        if ($this->query_params) {
            $url = sprintf("%s%s", $this->endpoint, $this->query . "?" . http_build_query($this->query_params));
        } else {
            $url = sprintf("%s%s", $this->endpoint, $this->query);
        }

        if ($this->request_method != "GET") {
            if ($this->content_type == "application/json") {
                $data = json_encode($this->post_field);
                $headers['Content-Type'] = "application/json";
                $headers['Content-Length'] = strlen($data);
            } else {
                $data = http_build_query($this->post_field);
                $headers['Content-Type'] = "application/x-www-form-urlencoded";
                $headers['Content-Length'] = strlen($data);

            }
        }

        $request = new Request(array(
            "request_method" => $this->request_method,
            "query"          => $this->query,
            "query_params"   => $this->query_params,
            "endpoint"       => $this->endpoint,
            "post_field"     => $this->post_field,
            "content_body"   => $data,
            "proxy"          => null,
            "headers"        => $headers,
            "url"            => $url,
        ));

        return $request;
    }
}