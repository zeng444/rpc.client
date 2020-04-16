<?php

namespace Janfish\Rpc;

use Janfish\Rpc\Client\ClientTrait;
use Janfish\Rpc\Client\Exception;

/**
 * Janfish RPC client
 * Author:Robert
 *
 * Class Client
 * @package Janfish\Rpc\Client
 */
class Client
{
    use ClientTrait;

    /**
     * @var
     */
    protected static $config;
    /**
     * @var
     */
    protected static $servicePrefix;
    /**
     *
     */
    protected static $clientName = 'Client';
    /**
     * @var
     */
    protected $serviceName;
    /**
     * @var string
     */
    protected $className;

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
        //这里做了负载算法
        if (isset($config[0])) {
            $config = $this->balance($config);
        }
        if (!$config) {
            throw new Exception('Config for `'.$this->serviceName.'` not found.');
        }
        if (!isset($config['id']) || !isset($config['secret']) || !isset($config['host'])) {
            throw new Exception('Config error');
        }
        $ctx = $this->make($this->serviceName, $methodName, $args, $config['id'], $config['secret'], $config['signType'] ?? 'sha1');
        if (!$ctx) {
            throw new Exception('data signature failed');
        }
        return $this->parse((self::getClient($config))->remoteCall($ctx));
    }

    /**
     * 载入批量调度器
     * Author:Robert
     *
     * @param $methodName
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($methodName, array $args)
    {
        $className = get_called_class();
        if ($className === self::$servicePrefix.self::$clientName) {
            return (new Client\Batch(self::$config))->$methodName(...$args);
        }
        $instance = new $className();
        return $instance->$methodName(...$args);
    }


    /**
     * Author:Robert
     *
     * @param string $service
     * @param string $methodName
     * @param array $args
     * @param string $id
     * @param string $secret
     * @param string $signType
     * @return string
     */
    protected function make(string $service, string $methodName, array $args, string $id, string $secret, string $signType = 'sha1'): string
    {
        $ctx = [
            'algorithm' => $signType,
            'appId' => $id,
            'service' => $service,
            'call' => $this->className.'::'.$methodName,
            'args' => $args,
            'timestamp' => microtime(true),
        ];
        $ctx['signature'] = $this->signature($id, $secret, $service, $ctx['call'], $ctx['timestamp'], $signType);
        return json_encode($ctx);
    }


}
