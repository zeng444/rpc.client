<?php

namespace Janfish\Rpc;

use Janfish\Rpc\Client\ClientInterface;
use Janfish\Rpc\Client\Http;
use Janfish\Rpc\Client\Exception;
use Janfish\Rpc\Client\Socket;

/**
 * Janfish RPC client
 * Author:Robert
 *
 * Class Client
 * @package Janfish\Rpc\Client
 */
class Client
{

    /**
     * @var
     */
    protected $serviceName;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var
     */
    protected static $config;

    /**
     * @var
     */
    protected static $servicePrefix;

    /**
     * initialize Rpc client
     * @param $config
     * @param $servicePrefix
     */
    public static function init(array $config, string $servicePrefix = 'Services\\'): void
    {
        self::$servicePrefix = $servicePrefix;
        self::$config = $config;
        static $initialized = false;
        if (!$initialized) {
            spl_autoload_register(function ($class) {
                if (strpos($class, self::$servicePrefix) === 0) {
                    $i = strrpos($class, '\\');
                    $className = substr($class, $i + 1);
                    $namespace = substr($class, 0, $i);
                    $definition = sprintf('namespace %s; class %s extends \\Janfish\\Rpc\\Client {}', $namespace, $className);
                    eval($definition);
                }
            });
            $initialized = true;
        }
    }

    /**
     * Http constructor.
     */
    public function __construct()
    {
        $className = get_class($this);
        $guess = explode('\\', preg_replace('/^'.preg_quote(self::$servicePrefix).'/', '', $className));
        $this->serviceName = $guess[0];
        $this->className = implode('\\', array_slice($guess, 1));
    }

    /**
     * handle the rpc call
     * Author:Robert
     *
     * @param $methodName
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public function __call(string $methodName, array $args)
    {
        $config = self::$config[$this->serviceName] ?? [];
        if (!$config) {
            throw new Exception('Config for `'.$this->serviceName.'` not found.');
        }
        if (!isset($config['id']) || !isset($config['secret']) || !isset($config['host'])) {
            throw new Exception('Config error');
        }
        return $this->parse((self::getClient($config))->remoteCall($this->make($methodName, $args, $config['id'], $config['secret'], $config['signType'] ?? 'sha1')));
    }

    /**
     * Author:Robert
     *
     * @param $methodName
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($methodName, array $args)
    {
        $className = get_called_class();
        $instance = new $className();
        return $instance->$methodName(...$args);
    }

    /**
     * Author:Robert
     *
     * @param array $config
     * @return ClientInterface
     */
    public static function getClient(array $config = []): ClientInterface
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

    /**
     * Make Request and generate signature
     * Author:Robert
     *
     * @param string $methodName
     * @param array $args
     * @param string $id
     * @param string $secret
     * @param string $signType
     * @return string
     */
    protected function make(string $methodName, array $args, string $id, string $secret, string $signType = 'sha1'): string
    {
        $ctx = [
            'algorithm' => $signType,
            'appId' => $id,
            'service' => $this->serviceName,
            'call' => $this->className.'::'.$methodName,
            'args' => $args,
            'timestamp' => microtime(true),
        ];
        //sort by dict
        $ctx['signature'] = $signType(sprintf('appId=%s&algorithm=%s&call=%s&secret=%s&service=%s&timestamp=%s', $id, $signType ?: 'sha1', $ctx['call'], $secret, $ctx['service'], $ctx['timestamp']));
        return json_encode($ctx, JSON_NUMERIC_CHECK);
    }

    /** parse response data
     * Author:Robert
     *
     * @param string $res
     * @return array
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
}
