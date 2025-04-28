<?php

namespace ObjectCode\K3\Core\Service;

use ObjectCode\K3\Core\Logger;
use ObjectCode\K3\Core\Request;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\PictureHandler;

class Configuration
{
    /**
     * Add configuration to basket
     *
     * @param $configurationId
     * @param $basket
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     */
    public function addToBasket($configurationId, $basket)
    {
        $configurationModel = $this->getConfigurationModel($configurationId);
        if (!$configurationModel) {
            $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_CONFIGURATION_ERROR');
            throw new \Exception($error);
        }
        $basketArticles = $configurationModel->getBasketProducts();
        if (Registry::getConfig()->getConfigParam('blOcK3CombineArticles')) {
            $configuration = $configurationModel->getConfiguration();
            $price = $this->calculateCombinedPrice($basketArticles);
            $article = oxNew('oxArticle');
            $descriptionHeader = '<div class="product_title_big"><h2>K3 Konfiguration ' . $configurationId . '</h2></div>';
            $descriptionList = '<ul>';
            foreach ($configuration->variables as $variable) {
                $descriptionList .= '<li>' . $variable->value . '</li>';
            }
            $descriptionList .= '</ul>';
            $descriptionLink = '<a href="' . $configuration->frontendURL .'" target="_blank">Link</a>';
            $description = $descriptionHeader . $descriptionList . $descriptionLink;
            $article->assign([
                'oxarticles__oxtitle' => new \OxField('K3 Konfiguration ' . $configurationId),
                'oxarticles__oxshortdesc' => new \OxField('K3 Konfiguration ' . $configurationId),
                //'oxarticles__oxlongdesc' => new \OxField($description), This does'nt work... Don't know why!
                'oxarticles__oxprice' => new \OxField($price),
                'oxarticles__oxstock' => new \OxField(1),
                'oxarticles__oxactive' => new \OxField(1),
                'oxarticles__oxissearch' => new \OxField(0),
                'oxarticles__oxhidden' => new \OxField(1),
                'oxarticles__oxartnum' => new \OxField('K3C_' . $configurationId),
            ]);
            $article->setArticleLongDesc($description);

            $local_image_path = $this->downloadAndSaveConfigurationImage($configuration->image, strtolower('K3C_' . $configurationId));
            $article->oxarticles__oxpic1 = new \OxField(basename($local_image_path));

            if ($article->save()) {
                $basketItem = $basket->addToBasket($article->oxarticles__oxid->value, 1, null, $this->getFormattedParams([]));
                $this->setBasketItemPrice($basket, $basketItem, $price);
            }
        } else {
            foreach ($basketArticles as $basketArticle) {
                $basketItem = $basket->addToBasket($basketArticle['id'], $basketArticle['amount'], null,
                    $this->getFormattedParams($basketArticle['params']));
                $this->setBasketItemPrice($basket, $basketItem, $basketArticle['price']);
            }
        }
    }

    /**
     * Return formatted params
     *
     * @param $configurationParams
     * @return array
     */
    protected function getFormattedParams($configurationParams): array
    {
        $encodedConfiguration = base64_encode(serialize($configurationParams));
        return [
            'k3' => $encodedConfiguration
        ];
    }

    /**
     * Set basket item price
     *
     * @param $basket
     * @param $basketItem
     * @param $price
     * @return void
     */
    protected function setBasketItemPrice($basket, $basketItem, $price)
    {
        if ($basketItem && $price) {
            $oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
            if ($basket->isCalculationModeNetto()) {
                $oPrice->setNettoPriceMode();
            } else {
                $oPrice->setBruttoPriceMode();
            }
            $oPrice->setPrice($price);
            $basketItem->setPrice($oPrice);
        }
    }

    /**
     * Create configuration model
     *
     * @param $configurationId
     * @return \ObjectCode\K3\Application\Model\Configuration|mixed
     */
    protected function getConfigurationModel($configurationId)
    {
        $configurationJson = $this->loadConfiguration($configurationId);
        $configurationObject = json_decode($configurationJson);
        if ($configurationObject) {
            $configuration = oxNew(\ObjectCode\K3\Application\Model\Configuration::class);
            $configuration->setConfiguration($configurationObject);
            return $configuration;
        }
    }

    /**
     * Load configuration
     * @param $configurationId
     * @return string
     */
    protected function loadConfiguration($configurationId)
    {
        $configuration = oxNew(Request::class)->getConfiguration($configurationId);
        if ($configuration) {
            return $configuration;
        }
        $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_NO_CONFIGURATION');
        throw new \Exception($error);
    }

    /**
     * Set ordered state
     *
     * @param $configurationId
     * @param $app
     * @return void
     */
    public function setOrdered($configurationId, $app)
    {
        $response = oxNew(Request::class)->setOrdered($configurationId, $app);
        if ($response) {
            Registry::get(Logger::class)->info('set ordered result', [$response]);
            return json_decode($response);
        }
    }

    private function calculateCombinedPrice($basketArticles): int
    {
        return $basketArticles[0]["price"];
    }

    private function downloadAndSaveConfigurationImage(string $remoteUrl, string $articleId): string
    {
        $remotePath = parse_url($remoteUrl, PHP_URL_PATH);
        $remoteExtension = pathinfo($remotePath, PATHINFO_EXTENSION);
        $localPath = "";
        $imageContent = file_get_contents($remoteUrl);
        if ($imageContent !== false) {
            $imageName = $articleId . '_main.' . $remoteExtension;
            $localPath = 'out/pictures/master/product/1/' . $imageName;

            $localDirectory = dirname($localPath);
            if (!is_dir($localDirectory)) {
                if (!mkdir($localDirectory, 0755, true)) {
                    $localPath = "";
                }
            }
            file_put_contents($localPath, $imageContent);
            $localPath = $imageName;
        } else {
            $error = error_get_last();
        }
        return $localPath;
    }
}
