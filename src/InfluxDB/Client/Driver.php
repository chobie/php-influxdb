<?php
namespace InfluxDB\Client;

interface Driver
{
    public function setDebug($bool);

    public function request(Http\Request $request);

    public function setOption(\Closure $closure);
}