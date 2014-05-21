<?php
namespace InfluxDB;

use InfluxDB\Client\Driver;

class Client {

    /** @var string $scheme */
    protected $scheme = "http";

    /** @var string $host */
    protected $host = "127.0.0.1";

    /** @var string $username */
    protected $username;

    /** @var string $password */
    protected $password;

    /** @var  string $dbname */
    protected $dbname;

    /** @var int $port */
    protected $port = 8086;

    /** @var  \InfluxDB\Driver */
    protected $drvier;

    /** @var bool $debug */
    protected $debug = false;

    public function __construct($hostspec, $username = "root", $password = "root", $dbname = null)
    {
        if (empty($hostspec)) {
            $hostspec = "http://127.0.0.1:8086";
        }
        $info = parse_url($hostspec);

        if (!empty($info['port'])) {
            $port = $info['port'];
        }
        if (!empty($info['host'])) {
            $host= $info['host'];
        }

        if (empty($username)) {
            $username = "root";
        }
        if (empty($password)) {
            $password = "root";
        }
        if (empty($port)) {
            $port = 8086;
        }

        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->scheme = $info['scheme'];

        $this->driver = new Client\Driver\CurlDriver();

        if (getenv("INFLUXPHP_DEBUG") == true) {
            $this->setDebug(true);
        }
    }

    public function setDriver(Driver $driver) {
        $this->driver = $driver;
    }

    public function changeDatabase($name)
    {
        $this->dbname = $name;
    }

    protected function getEndpoint()
    {
        return sprintf("%s://%s:%s", $this->scheme, $this->host, $this->port);
    }

    protected function getAuth()
    {
        return array(
            "u" => $this->username,
            "p" => $this->password,
        );
    }

    public function ping()
    {
        return $this->api("GET", "/ping", null, array());
    }

    public function listServers()
    {
        return $this->api("GET", "/cluster/servers", $this->getAuth());
    }

    public function getShards()
    {
        return $this->api("GET", "/cluster/shards", $this->getAuth());
    }

    public function isInSync()
    {
        return $this->api("GET", "/sync", $this->getAuth());
    }

    public function listInterfaces()
    {
        return $this->api("GET", "/interfaces", $this->getAuth());
    }

    public function createDatabase($name)
    {
        return $this->api("POST", "/db", $this->getAuth(), array("name" => $name));
    }

    protected function checkDbAndRaiseError()
    {
        if (empty($this->dbname)) {
            throw new \InvalidArgumentException("you have to call changeDatabase(\$dbname) at first");
        }
    }

    public function dropDatabase($name)
    {
        $this->checkDbAndRaiseError();
        return $this->api("DELETE", "/db/{$name}", $this->getAuth());
    }

    public function listClusterAdmin()
    {
        return $this->api("GET", "/cluster_admins", $this->getAuth());
    }

    public function authenticateClusterAdmin()
    {
        return $this->api("GET", "/cluster_admins/authenticate", $this->getAuth());
    }

    public function createClusterAdmin($user, $password)
    {
        return $this->api("POST", "/cluster_admins/authenticate", $this->getAuth(), array(
            "name" => $user,
            "password" => $password,
        ));
    }

    public function listDbContinuousQueries($db)
    {
        $this->checkDbAndRaiseError();
        return $this->api("GET", "/db/{$db}/continuous_queries", $this->getAuth());
    }

    public function createDbContinuousQueries($db, $query)
    {
        $this->checkDbAndRaiseError();
        return $this->api("POST", "/db/{$db}/continuous_queries", $this->getAuth(), array('query' => $query));
    }

    public function deleteDbContinuousQueries($db, $id)
    {
        $this->checkDbAndRaiseError();
        return $this->api("DELETE", "/db/{$db}/continuous_queries/{$id}", $this->getAuth());
    }

    public function forceRaftCompaction()
    {
        return $this->api("POST", "/raft/force_compaction", $this->getAuth());
    }

    public function updateClusterAdmin($user, $password)
    {
        return $this->api("POST", "/cluster_admins/{$user}", $this->getAuth(), array(
            "password" => $password,
        ));
    }

    public function deleteClusterAdmin($user)
    {
        return $this->api("DELETE", "/cluster_admins/{$user}", $this->getAuth());
    }

    public function query($query, $timePrecision = 's')
    {
        $this->checkDbAndRaiseError();
        $this->checkTimePrecisionAndRaise($timePrecision);

        $params = $this->getAuth();
        $params['q'] = $query;
        $params['time_precision'] = $timePrecision;

        $result = $this->api("GET", "/db/{$this->dbname}/series", $params);
        if (empty($result)) {
            return new Client\SerializedSeriesCollection();
        }

        $c = new Client\SerializedSeriesCollection();
        foreach ($result as $v) {
            $c->append(new Client\SerializedSeries($v['name'], $v['columns'], $v['points']));
        }
        return $c;
    }

    public function dropSeries($name)
    {
        $this->checkDbAndRaiseError();
        return $this->api("DELETE", "/db/{$this->dbname}/series/{$name}", $this->getAuth());
    }

    protected function checkTimePrecisionAndRaise($timePrecision)
    {
        switch ($timePrecision) {
        case "u":
        case "m":
        case "s":
        case "":
            break;
        default:
            throw new \InvalidArgumentException(sprintf("time precision %s does not support yet", $timePrecision));
        }
    }

    public function writePoints($name, $points, $timePrecision = "s")
    {
        $this->checkDbAndRaiseError();
        $this->checkTimePrecisionAndRaise($timePrecision);

        $params = array_merge($this->getAuth(), array("time_precision" => $timePrecision));
        $payload = array(
            "name" => $name,
            "columns" => array_keys($points[0]),
            "points" => array(),
            "timePrecision" => $timePrecision
        );
        foreach ($points as $point) {
            $payload['points'][] = array_values($point);
        }

        return $this->api("POST", "/db/{$this->dbname}/series", $params, array($payload));
    }

    public function listDatabase()
    {
        return $this->api("GET", "/db", $this->getAuth());
    }

    public function setDebug($bool)
    {
        $this->debug = $bool;
    }

    protected function api($http_method = "GET", $query, $params, $post_field = array())
    {
        $builder = new Client\Http\RequestBuilder();
        $builder->setRequestMethod($http_method);
        $builder->setEndpoint($this->getEndpoint());
        $builder->setQuery($query);
        $builder->setQueryParams($params);
        $builder->setPostField($post_field);
        if (count($post_field)) {
            $builder->setContentType("application/json");
        }
        $request = $builder->build();

        $this->driver->setDebug($this->debug);
        $res = $this->driver->request($request);

        if (strpos($res[0]['HTTP_CODE'], "40") === 0) {
            throw new \RuntimeException("errors: " . $res[1]);
        } else if (strpos($res[0]['HTTP_CODE'], "50") === 0) {
            throw new \RuntimeException("errors: " . $res[1]);
        }

        if ($res[0]['Content-Type'] == "application/json") {
            return json_decode($res[1], true);
        } else {
            if ($res[1] == "true") {
                return true;
            }

            return $res[1];
        }
    }

}