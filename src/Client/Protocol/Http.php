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
class Http implements ProtocolInterface
{

    /**
     * Author:Robert
     *
     * @var string
     */
    protected $host;

    /**
     * Author:Robert
     *
     * @var int
     */
    protected $timeout = 5;

    /**
     * Author:Robert
     *
     * @var int
     */
    protected $connectTimeout = 2;


    /**
     * Http constructor.
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
     * Author:Robert
     *
     * @param string $ctx
     * @return string
     * @throws Exception
     */
    public function remoteCall(string $ctx): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }
        if ($this->timeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, "X.Y R&D Apollo Program");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ctx);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Expect:"]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 !== $statusCode) {
            throw new Exception($response, $statusCode);
        }
        curl_close($ch);
        return $response;
    }
}
