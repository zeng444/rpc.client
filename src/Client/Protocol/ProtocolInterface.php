<?php

namespace Janfish\Rpc\Client\Protocol;

/**
 * Author:Robert
 *
 * Interface ProtocolInterface
 * @package Janfish\Rpc\Client
 */
interface ProtocolInterface
{

    public function __construct(array $options = []);

    /**'
     * Author:Robert
     *
     * @param string $ctx
     * @return string
     */
    public function remoteCall(string $ctx): string;
}
