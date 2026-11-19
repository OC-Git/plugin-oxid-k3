<?php

namespace ObjectCode\K3\Application\Controller;

use ObjectCode\K3\Core\ArticleFilter;
use ObjectCode\K3\Core\Connector;
use ObjectCode\K3\Core\Logger;
use ObjectCode\K3\Core\Output;
use ObjectCode\K3\Core\Service\Export;
use ObjectCode\K3\Core\Validation;
use OxidEsales\Eshop\Core\Registry;

class ProductExportController extends \OxidEsales\Eshop\Application\Controller\FrontendController
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

        $this->outputExport();
    }

    /**
     * Output export
     *
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function outputExport()
    {
        try {
            $connector = oxNew(Connector::class);
            $this->validateSecret($connector);
            try {
                $filters = oxNew(ArticleFilter::class)->parse($_GET);
            } catch (\InvalidArgumentException $e) {
                Registry::get(Logger::class)->error('Invalid article filter', [$e->getMessage(), __METHOD__]);
                Registry::get(Output::class)->json(['message' => $e->getMessage()], 400);
            }
            $service = oxNew(Export::class);
            $service->setFilters($filters);
            Registry::get(Output::class)->json($service->getProducts(), 200);
        } catch (\Exception $e) {
            Registry::get(Logger::class)->error('Could not export articles', [
                $e->getMessage(),
                __METHOD__
            ]);
        }
        exit;
    }

    /**
     * Validate secret
     *
     * @param $connector
     * @return void
     */
    protected function validateSecret($connector)
    {
        if (!Registry::get(Validation::class)->isSecretInHeader($connector->getSecret())) {
            Registry::get(Logger::class)->error('Secret is not valid',
                [__METHOD__]);
            Registry::get(Output::class)->json(['message' => 'Secret is not valid.'], 403);
        }
    }
}