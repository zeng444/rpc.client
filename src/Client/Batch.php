<?php

namespace Janfish\Rpc\Client;


/**
 * Author:Robert
 *  - 同服务分组
 *  - 分组分别签名并调用
 *  - 分组返回分别赋值
 * Class Caller
 * @package Janfish\Rpc\Client
 */
class Batch
{

    use ClientTrait;

    /**
     * Author:Robert
     *
     * @var array
     */
    private $_config;

    /**
     * Author:Robert
     *
     * @var string
     */
    public static $servicePrefix = 'Services\\';

    /**
     * Caller constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * Author:Robert
     *
     * @param array $commands
     * @return array
     */
    public function findOutService(array $commands): array
    {
        $data = [];
        foreach ($commands as $assign => $command) {
            $class = $command['class'] ?? ($command[0] ?? '');
            $class = (strpos($class, '\\') === 0) ? substr($class, 1) : $class;
            $guess = explode('\\', preg_replace('/^'.preg_quote(self::$servicePrefix).'/', '', $class));
            $data[$guess[0]][] = [
                'assign' => $assign,
                'remote' => [
                    'call' => implode('\\', array_slice($guess, 1)).'::'.($command['method'] ?? ($command[1] ?? '')),
                    'args' => $command['args'] ?? ($command[2] ?? []),
                ],
            ];
        }
        return $data;
    }


    /**
     * Author:Robert
     *
     * @param array $batchCommand
     * @return array|mixed
     * @throws Exception
     */
    public function call(array $batchCommand)
    {
        $isSingle = (isset($batchCommand['class']) && isset($batchCommand['method']));
        $batchCommand = $isSingle ? [$batchCommand] : $batchCommand;
        $configs = $this->_config;
        $services = $this->findOutService($batchCommand);
        $return = [];
        foreach ($services as $name => $service) {
            if (!isset($configs[$name])) {
                throw new Exception('不存在的服务');
            }
            $config = $configs[$name];
            //这里做了负载算法
            if (isset($config[0])) {
                $config = $this->balance($config);
            }
            $commands = array_column($service, 'remote');
            $rep = $this->parse((self::getClient($config))->remoteCall($this->make($name, $commands, $config['id'], $config['secret'], $config['signType'] ?? 'sha1')));
            foreach ($service as $index => $item) {
                $return[$item['assign']] = $rep[$index];
            }
        }
        $return = $isSingle ? current($return) : $return;
        return $return;

    }


    /**
     * Author:Robert
     *
     * @param string $service
     * @param array $commands
     * @param string $id
     * @param string $secret
     * @param string $signType
     * @return string
     */
    protected function make(string $service, array $commands, string $id, string $secret, string $signType = 'sha1'): string
    {
        $ctx = [
            'algorithm' => $signType,
            'appId' => $id,
            'service' => $service,
            'timestamp' => microtime(true),
            'batch' => $commands,
        ];
        $call = implode(',', array_column($commands, 'call'));
        $ctx['signature'] = $this->signature($id, $secret, $service, $call, $ctx['timestamp'], $signType);
        return json_encode($ctx);
    }
}
