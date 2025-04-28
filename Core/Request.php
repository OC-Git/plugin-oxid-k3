<?php

namespace ObjectCode\K3\Core;

use OxidEsales\Eshop\Core\Registry;

class Request
{
    /**
     * Configuration endpoint
     *
     * @var string
     */
    protected string $configurationEndpoint = 'https://k3-api.objectcode.de/api/v1.0/cfg/{cfg}/shop';

    /**
     * Order endpoint
     *
     * @var string
     */
    protected string $orderEndpoint = 'https://k3-api.objectcode.de/api/v1.0/app/{code}/cfg/{cfg}/save-only';

    /**
     * Test configuration endpoint
     *
     * @var string
     */
    protected string $configurationEndpointTest = 'https://k3-test-api.objectcode.de/api/v1.0/cfg/{cfg}/shop';

    /**
     * Test order endpoint
     *
     * @var string
     */
    protected string $orderEndpointTest = 'https://k3-test-api.objectcode.de/api/v1.0/app/{code}/cfg/{cfg}/save-only';

    /**
     * Return token
     *
     * @return string
     */
    protected function getToken(): string
    {
        $connector = oxNew(Connector::class);
        return $connector->getToken();
    }

    /**
     * Return configuration endpoint
     *
     * @return string
     */
    protected function getConfigurationEndpoint(): string
    {
        if (Registry::getConfig()->getConfigParam('blOcK3TestMode')) {
            return $this->configurationEndpointTest;
        }
        return $this->configurationEndpoint;
    }

    /**
     * Return order endpoint
     *
     * @return string
     */
    protected function getOrderEndpoint(): string
    {
        if (Registry::getConfig()->getConfigParam('blOcK3TestMode')) {
            return $this->orderEndpointTest;
        }
        return $this->orderEndpoint;
    }

    /**
     * Return configuration response
     *
     * @param $configurationId
     * @return bool|string
     * @throws \Exception
     */
    public function getConfiguration($configurationId)
    {
        $url = str_replace('{cfg}', $configurationId, $this->getConfigurationEndpoint());
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        $header = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getToken()
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($statusCode != 200) {
            $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_STATUS_CODE');
            throw new \Exception(sprintf($error, $statusCode));
        }
        if (!$result) {
            $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_NO_RESPONSE');
            throw new \Exception($error);
        }

        return $result;
    }

    /**
     * Set configuration as ordered
     *
     * @param $configurationId
     * @param $app
     * @return bool|string
     * @throws \Exception
     */
    public function setOrdered($configurationId, $app)
    {
        $url = str_replace(['{code}', '{cfg}'], [$app, $configurationId], $this->getOrderEndpoint());
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        $header = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getToken()
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            'state' => 'ordered',
        ]));
        $result = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode != 200) {
            $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_STATUS_CODE');
            throw new \Exception(sprintf($error, $statusCode));
        }
        if (!$result) {
            $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_NO_RESPONSE');
            throw new \Exception($error);
        }
        return $result;
    }
}
