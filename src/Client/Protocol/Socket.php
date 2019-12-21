<?php

namespace Janfish\Rpc\Client\Protocol;

use Janfish\Rpc\Client\Exception;

/**
 * Janfish RPC client
 * Author:Robert
 *
 * Class Client
 * @package Janfish\Rpc\Client
 */
class Socket implements ProtocolInterface
{

    /**
     * Author:Robert
     *
     * @var
     */
    protected $_di;

    /**
     * Author:Robert
     *
     * @var
     */
    protected $timeout = 5;

    /**
     * Author:Robert
     *
     * @var int
     */
    protected $connectTimeout = 2;


    /**
     * Author:Robert
     *
     * @var mixed
     */
    public $host = 'tcp://127.0.0.1:8989';


    const RPC_EOL = "\r\n";

    /**
     * Socket constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['host'])) {
            $this->host = $options['host'];
        }
        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
        if (isset($options['connectTimeout'])) {
            $this->connectTimeout = $options['connectTimeout'];
        }
    }

    /**
     * stream_socket_client
     * Author:Robert
     *
     * @param string $ctx
     * @return mixed
     * @throws Exception
     */
    public function remoteCall(string $ctx): string
    {
        $endPos = substr(self::RPC_EOL, 0, 1);
        $fp = @stream_socket_client($this->host, $errNo, $errStr, $this->connectTimeout);
        if (!$fp) {
            throw new Exception("stream_socket_client fail errno={$errNo} errstr={$errStr}");
        }
        fwrite($fp, $ctx.self::RPC_EOL);
        stream_set_timeout($fp, $this->timeout);
        $res = '';
        while (!feof($fp)) {
            $tmp = fgets($fp, 1024);
            $info = stream_get_meta_data($fp);
            if ($info['timed_out']) {
                throw new Exception("stream_socket_client timeout");
            }
            $pos = strpos($tmp, $endPos);
            if ($pos !== false) {
                $res .= substr($tmp, 0, $pos);
                break;
            } else {
                $res .= $tmp;
            }
        }
        fclose($fp);
        return $res;
    }
}
