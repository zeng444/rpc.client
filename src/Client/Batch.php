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
     * @param $commands
     * @return array
     * @throws Exception
     */
    public function findOutService(array $commands): array
    {
        $data = [];
        foreach ($commands as $assign => $command) {
            if (!isset($command['class']) || !isset($command['method'])) {
                throw new Exception('Method or class params not exist');
            }
            $command['class'] = (strpos($command['class'], '\\') === 0) ? substr($command['class'], 1) : $command['class'];
            $guess = explode('\\', preg_replace('/^'.preg_quote(self::$servicePrefix).'/', '', $command['class']));
            $data[$guess[0]][] = [
                'assign' => $assign,
                'remote' => [
                    'call' => implode('\\', array_slice($guess, 1)).'::'.$command['method'],
                    'args' => $command['args'] ?? [],
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
        $config = $this->_config;
        $services = $this->findOutService($batchCommand);
        $return = [];
        foreach ($services as $name => $service) {
            if (!isset($config[$name])) {
                throw new Exception('不存在的服务');
            }
            $commands = array_column($service, 'remote');
            $rep = $this->parse((self::getClient($config[$name]))->remoteCall($this->make($name, $commands, $config[$name]['id'], $config[$name]['secret'], $config[$name]['signType'] ?? 'sha1')));
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

