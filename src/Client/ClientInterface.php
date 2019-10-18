<?php

namespace Janfish\Rpc\Client;

/**
 * Author:Robert
 *
 * Interface ClientInterface
 * @package Janfish\Rpc\Client
 */
interface ClientInterface
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
