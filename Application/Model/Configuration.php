<?php

namespace ObjectCode\K3\Application\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use function oxNew;

class Configuration
{
    /**
     * @var object
     */
    protected object $configuration;

    /**
     * Configuration url
     *
     * @var string
     */
    protected string $configurationUrl = 'https://k3.objectcode.de/app/?code=';

    /**
     * Test configuration url
     *
     * @var string
     */
    protected string $configurationUrlTest = 'https://k3.objectcode.de/test/app/?code=';

    /**
     * @return object
     */
    public function getConfiguration(): object
    {
        return $this->configuration;
    }

    /**
     * @param object $configuration
     */
    public function setConfiguration(object $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Return configuration url
     *
     * @return string
     */
    public function getConfigurationUrl(): string
    {
        if (Registry::getConfig()->getConfigParam('blOcK3TestMode')) {
            return $this->configurationUrlTest;
        }
        return $this->configurationUrl;
    }

    /**
     * Return configuration variables
     *
     * @return array
     */
    protected function getConfigurationVariables(): array
    {
        $configuration = $this->getConfiguration();
        return $configuration->variables;
    }

    /**
     * Return configuration products
     *
     * @return array
     */
    protected function getConfigurationProducts(): array
    {
        $configuration = $this->getConfiguration();
        return $configuration->bom;
    }

    /**
     * Return current configuration url
     *
     * @return string
     */
    protected function getCurrenConfigurationUrl(): string
    {
        $url = $this->getConfigurationUrl();
        $configuration = $this->getConfiguration();
        return $url . $configuration->code;
    }

    /**
     * Return basket articles
     *
     * @return array
     */
    public function getBasketProducts(): array
    {
        $basketArticles = [];
        $articles = $this->getConfigurationProducts();
        foreach ($articles as $article) {
            $basketArticles[] = $this->getBasketProduct($article);
        }
        return $basketArticles;
    }

    /**
     * Return basket article
     *
     * @param $article
     * @return array
     */
    protected function getBasketProduct($article): array
    {
        $params = $this->getBasketProductParams();
        $params['id'] = $this->getOxidFromArticleNumber($article->article);
        $params['amount'] = $article->qty;
        $params['price'] = $this->getConfigurationPrice();
        return [
            'id' => $this->getOxidFromArticleNumber($article->article),
            'amount' => $article->qty,
            'params' => $params,
            'price' => $this->getConfigurationPrice(),
        ];
    }

    /**
     * Return configuration price
     *
     * @return float
     */
    protected function getConfigurationPrice(): float
    {
        $configuration = $this->getConfiguration();
        return $configuration->price;
    }

    /**
     * Return persparams
     *
     * @return array
     */
    protected function getBasketProductParams(): array
    {
        $configuration = $this->getConfiguration();
        $variables = $this->getConfigurationVariables();
        $params = [];
        $params['code'] = $configuration->code;
        $params['app'] = $configuration->app;
        $params['url'] = $this->getCurrenConfigurationUrl();
        $params['price'] = $this->getConfigurationPrice();
        foreach ($variables as $variable) {
            $params['variables'][] = $this->getVariableParams($variable);
        }
        return $params;
    }

    /**
     * Return variable params as array for persparams
     *
     * @param $variable
     * @return array
     */
    protected function getVariableParams($variable): array
    {
        $label = $variable->variable->label;
        $value = $variable->value;
        if (isset($variable->selected) && isset($variable->selected->value)) {
            $value = $variable->selected->value;
            if (isset($variable->selected->label) && $variable->selected->label != '') {
                $label = $variable->selected->label;
            }
        }
        return [
            'label' => $label,
            'value' => $value,
        ];
    }

    /**
     * Return oxid form article number
     *
     * @param string $articleNumber
     * @return string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function getOxidFromArticleNumber(string $articleNumber): string
    {
        $query = "select oxid from oxarticles where oxartnum = :oxartnum limit 1";
        $oxid = DatabaseProvider::getDb()->getOne($query, [':oxartnum' => $articleNumber]);
        if ($oxid) {
            return $oxid;
        }

        $error = Registry::getLang()->translateString('OC_K3_EXCEPTION_ARTICLE_NOT_FOUND');
        throw new \Exception(sprintf($error, $articleNumber));
    }

}
