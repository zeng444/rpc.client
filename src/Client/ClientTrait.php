<?php

namespace Janfish\Rpc\Client;

use Janfish\Rpc\Client\Protocol\ProtocolInterface;
use Janfish\Rpc\Client\Protocol\Http;
use Janfish\Rpc\Client\Protocol\Socket;

/**
 * Author:Robert
 *
 * Trait ClientTrait
 * @package Janfish\Rpc\Client
 */
trait ClientTrait
{

    /**
     * Author:Robert
     *
     * @param string $res
     * @return mixed
     * @throws Exception
     */
    protected function parse(string $res)
    {
        $ctx = json_decode($res, true);
        if (isset($ctx['ok']) && $ctx['ok']) {
            return $ctx['data'];
        }
        $message = $ctx['error'];
        if (isset($ctx['trace']) && $ctx['trace']) {
            $message .= "\n{$ctx['trace']}";
        }
        throw new Exception($message ?: "exception data:".$res);
    }

    /**
     * 负载均衡RR
     * Author:Robert
     *
     */
    protected function balance(array $configs=[])
    {
        return $configs[array_rand($configs)];
    }

    /**
     * 签名算法
     * Author:Robert
     *
     * @param $appId
     * @param $appSecret
     * @param $service
     * @param $call
     * @param $timestamp
     * @param string $signType
     * @return mixed
     */
    protected function signature(string $appId, string $appSecret, string $service, string $call, string $timestamp, string $signType = 'sha1'): string
    {
        //sort by dict
        return $signType(sprintf('appId=%s&algorithm=%s&call=%s&secret=%s&service=%s&timestamp=%s', $appId, $signType, $call, $appSecret, $service, $timestamp));
    }

    /**
     * Author:Robert
     *
     * @param array $config
     * @return ProtocolInterface
     */
    public static function getClient(array $config = []): ProtocolInterface
    {
        //TODO lake of web socket client
        $host = $config['host'] ?? '';
        if (preg_match('/^http/i', $host)) {
            return new Http($config);
        } elseif (preg_match('/^tcp/i', $host)) {
            return new Socket($config);
        } else {
            return new Socket($config);
        }
    }

}