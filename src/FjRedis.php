<?php
namespace FlagUpDown;

use FlagUpDown\Exceptions\StreamException;
use FlagUpDown\Utils\CommandEncode;
use FlagUpDown\Utils\RespDecode;

class FjRedis
{
    protected $redis;
    protected $host;
    protected $port;
    protected $db;
    protected $authPassword;
    protected $connectTimeout;
    protected $connectFailures;
    protected $maxConnectRetries;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, int $db = 0, string $authPassword = null, float $connectTimeout = 3.0)
    {
        $this->host           = $host;
        $this->port           = $port;
        $this->connectTimeout = $connectTimeout;
        $this->authPassword   = $authPassword;
        $this->selectedDb     = $db;

        $this->maxConnectRetries = 0;
    }

    public function __destruct()
    {
        if ($this->is_connected()) {
            $this->close();
        }
    }

    public function set_max_connect_retries(int $retries)
    {
        $this->maxConnectRetries = $retries;
        return $this;
    }

    public function stream_set_timeout(int $timeout)
    {
        if (!$this->is_connected()) {
            $this->connect();
        }
        \stream_set_timeout($this->redis, $timeout);
    }

    public function connect()
    {
        if ($this->is_connected()) {
            return $this;
        }
        $flags = STREAM_CLIENT_CONNECT;
        if ($this->port !== null) {
            $remote_socket = 'tcp://' . $this->host . ':' . $this->port;
            $flags         = $flags | STREAM_CLIENT_PERSISTENT;
        } else {
            $remote_socket = 'unix://' . $this->host;
        }
        $this->redis = @stream_socket_client($remote_socket, $errno, $errstr, $this->connectTimeout, $flags);
        if (!$this->redis) {
            $this->connectFailures++;
            if ($this->connectFailures <= $this->maxConnectRetries) {
                return $this->connect();
            }
            $this->connectFailures = 0;
            $errmsg                = "Connect Redis {$this->host}:{$this->port} failed after $this->connectFailures failures.Last Error : ({$errno}) {$errstr}";
            throw new \Exception($errmsg);
        }

        $this->connectFailures = 0;

        if ($this->authPassword) {
            $this->auth($this->authPassword);
        }
        if ($this->selectedDb !== 0) {
            $this->select($this->selectedDb);
        }
    }

    public function is_connected()
    {
        return $this->redis !== null && !feof($this->redis);
    }

    public function close()
    {
        $result = true;
        if ($this->redis) {
            fclose($this->redis);
            $this->redis = null;
        }
        return $result;
    }

    public function auth(string $password)
    {
        $response           = $this->__call('auth', array($password));
        $this->authPassword = $password;
        return $response;
    }

    public function select(int $index)
    {
        $response         = $this->__call('select', array($index));
        $this->selectedDb = $index;
        return $response;
    }

    public function __call($name, $args)
    {
        $name = strtolower($name);
        // 把参数数组扁平化，变为只有一层
        $args = self::_flatten_array($args);
        array_unshift($args, $name);
        $command = CommandEncode::array_encode($args);
        $this->_write_to_redis($command);
        $reply = RespDecode::decode($this->redis);
        return $reply;
    }

    protected function _write_to_redis(string $command)
    {
        if (!$this->is_connected()) {
            $this->connect();
        }
        $commandLen = strlen($command);
        $lastFailed = false;
        for ($written = 0; $written < $commandLen; $written += $fwrite) {
            $fwrite = fwrite($this->redis, substr($command, $written));
            if ($fwrite === false || ($fwrite == 0 && $lastFailed)) {
                $this->close();
                throw new StreamException('Failed to write entire command to stream');
            }
            $lastFailed = ($fwrite == 0);
        }
    }

    protected static function _flatten_array(array $array)
    {
        $return = array();
        array_walk_recursive($array, function ($value) use (&$return) {
            $return[] = $value;
        });
        return $return;
    }
}
