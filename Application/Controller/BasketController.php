<?php

namespace ObjectCode\K3\Application\Controller;

//use ObjectCode\K3\Application\Model\Configuration;
use ObjectCode\K3\Core\Logger;
use ObjectCode\K3\Core\Output;
use OxidEsales\Eshop\Core\Registry;

class BasketController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * render
     *
     * @return string|void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function render()
    {
        if (!Registry::getConfig()->getConfigParam('blOcK3Active')) {
            Registry::get(Output::class)->json(['message' => 'Module not active.'], 503);
        }

        $configurationId = Registry::getRequest()->getRequestParameter('cfg');
        if ( !$configurationId ) {
            Registry::get(Output::class)->json(['message' => 'No cfg parameter found in request.'], 503);
        }

        try {
            $basket = Registry::getSession()->getBasket();
            $service = oxNew(\ObjectCode\K3\Core\Service\Configuration::class);
            $service->addToBasket($configurationId, $basket);
        } catch (\Exception $e) {
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($e->getMessage());
            Registry::get(Logger::class)->error('Could not add configuration to basket', [
                $configurationId,
                $e->getMessage(),
                __METHOD__
            ]);
        } catch ( \Throwable $e) {
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($e->getMessage());
            Registry::get(Logger::class)->error('Could not add configuration to basket', [
                $configurationId,
                $e->getMessage(),
                __METHOD__
            ]);
        }

        Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeUrl().'cl=basket');
        exit;
    }
}
