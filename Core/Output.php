<?php

namespace ObjectCode\K3\Core;

class Output
{
    /**
     * HTTP Status mapping
     *
     * @var array|string[]
     */
    protected array $httpStatusMapping = [
        200 => 'HTTP/1.1 200 OK',
        400 => 'HTTP/1.1 400 Bad Request',
        401 => 'HTTP/1.1 401 Unauthorized',
        403 => 'HTTP/1.1 403 Forbidden',
        500 => 'HTTP/1.1 500 Internal Server Error',
        503 => 'HTTP/1.1 503 Service Unavailable',
    ];

    /**
     * Output json
     *
     * @param mixed $data
     * @param int $httpStatusCode
     * @param string $httpStatusMessage
     * @return void
     */
    public function json($data, int $httpStatusCode = 200, string $httpStatusMessage = '')
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        if (!$httpStatusMessage) {
            $httpStatusMessage = $this->httpStatusMapping[$httpStatusCode];
        }
        \OxidEsales\Eshop\Core\Registry::getUtils()->setHeader($httpStatusMessage);
        \OxidEsales\Eshop\Core\Registry::getUtils()->setHeader("Content-Type: application/json; charset=utf8");
        \OxidEsales\Eshop\Core\Registry::getUtils()->showMessageAndExit($data);
        exit;
    }
}