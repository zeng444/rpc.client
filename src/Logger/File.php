<?php

namespace Janfish\Rpc\Logger;

use Janfish\Rpc\Server\Exception;
use SeasLog;

/**
 * Author:Robert
 *
 * Class Logger
 * @package Janfish\Rpc\Log
 */
class File
{

    /**
     * Author:Robert
     *
     * @var array
     */
    protected $_config;


    /**
     * Author:Robert
     *
     * @var
     */
    public $logPath;

    /**
     * Logger constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config;
        if (isset($config['logPath'])) {
            $this->logPath = $config['logPath'];
        }
        if (!$this->logPath || !is_readable($this->logPath)) {
            throw new Exception(' 日志文件不存在');
        }
        SeasLog::setLogger('janfish.rpc');
        SeasLog::setBasePath($this->logPath);
        SeasLog::setDatetimeFormat('Y-m-d H:i:s');
    }

    /**
     * Author:Robert
     *
     * @param string $msg
     * @param string $level
     * @return bool
     */
    public function write(string $msg, string $level = SEASLOG_DEBUG): bool
    {
        return SeasLog::log($level, $msg);
    }

    /**
     * Author:Robert
     *
     * @param string $msg
     * @return bool
     */
    public function debug(string $msg): bool
    {
        return $this->write($msg, SEASLOG_DEBUG);
    }
}
