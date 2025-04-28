<?php

namespace ObjectCode\K3\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleSettingNotFountException;

class Connector
{
    /**
     * @var string
     */
    protected string $token = '';

    /**
     * @var string
     */
    protected string $secret = '';

    /**
     * @var string
     */
    protected string $basketUrl = '';

    /**
     * @var string
     */
    protected string $productExportUrl = '';

    /**
     * @var string
     */
    protected string $connectorUrl = '';

    /**
     * @return string
     */
    public function getToken(): string
    {
        if (!$this->token) {
            $token = Registry::getConfig()->getConfigParam('sOcK3AuthToken');
            if ($token) {
                $this->token = $token;
            }
        }
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        if (!$this->secret) {
            $secret = Registry::getConfig()->getConfigParam('sOcK3AuthSecret');
            if ($secret) {
                $this->secret = $secret;
            }
        }
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getBasketUrl(): string
    {
        if (!$this->basketUrl) {
            $this->basketUrl = $this->getControllerUrl('oc_ock3_basketcontroller');
        }
        return $this->basketUrl;
    }

    /**
     * @param string $basketUrl
     */
    public function setBasketUrl(string $basketUrl): void
    {
        $this->basketUrl = $basketUrl;
    }

    /**
     * @return string
     */
    public function getProductExportUrl(): string
    {
        if (!$this->productExportUrl) {
            $this->productExportUrl = $this->getControllerUrl('oc_ock3_productexportcontroller');
        }

        return $this->productExportUrl;
    }

    /**
     * @param string $productExportUrl
     */
    public function setProductExportUrl(string $productExportUrl): void
    {
        $this->productExportUrl = $productExportUrl;
    }

    /**
     * @return string
     */
    public function getConnectorUrl(): string
    {
        if (!$this->connectorUrl) {
            $this->connectorUrl = $this->getControllerUrl('oc_ock3_connectorcontroller');
        }
        return $this->connectorUrl;
    }

    /**
     * @param string $connectorUrl
     */
    public function setConnectorUrl(string $connectorUrl): void
    {
        $this->connectorUrl = $connectorUrl;
    }

    /**
     * Return controller url
     *
     * @param $controller
     * @return string
     */
    protected function getControllerUrl($controller): string
    {
        $url = Registry::getConfig()->getShopHomeUrl(). 'cl=' . $controller;

        return Registry::getUtilsUrl()->cleanUrl($url, ['force_sid', 'sid']);
    }

    /**
     * Save auth information
     *
     * @return bool
     */
    public function save(): bool
    {
        $moduleSettingBridge = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettingBridgeInterface::class);

        $moduleSettingBridge->save('sOcK3AuthSecret', $this->getSecret(), 'ock3');
        $moduleSettingBridge->save('sOcK3AuthToken', $this->getToken(), 'ock3');

        $secret = $moduleSettingBridge->get('sOcK3AuthSecret', 'ock3');
        $token = $moduleSettingBridge->get('sOcK3AuthToken', 'ock3');

        if (!$secret || !$token) {
            //reset values in db
            Registry::getConfig()->saveShopConfVar('str', 'sOcK3AuthSecret', null,
                Registry::getConfig()->getShopId(),
                'module:ock3');

            Registry::getConfig()->saveShopConfVar('str', 'sOcK3AuthToken', null,
                Registry::getConfig()->getShopId(),
                'module:ock3');

            throw new \Exception('Could not save secret and token');
        }

        return true;
    }

}
