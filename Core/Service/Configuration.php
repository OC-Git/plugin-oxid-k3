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

        $configuration = $configurationModel->getConfiguration();
        $configurationCode = $this->toText((string)($configuration->code ?? ''));
        $persParam = $this->getPersParams($configuration);

        if (Registry::getConfig()->getConfigParam('blOcK3CombineArticles')) {
            $price = $configuration->price;
            $article = oxNew('oxArticle');
            $descriptionHeader = '<div class="product_title_big"><h2>K3 Konfiguration ' . $configurationCode . '</h2></div>';
            $descriptionList = $configuration->description;
            $descriptionLink = '<a href="' . htmlspecialchars((string)($configuration->frontendURL ?? ''), ENT_QUOTES) . '" target="_blank">Link</a>';
            $description = $descriptionHeader . $descriptionList . $descriptionLink;
            $article->assign([
                'oxarticles__oxtitle' => 'K3 Konfiguration ' . $configurationCode,
                'oxarticles__oxshortdesc' => 'K3 Konfiguration ' . $configurationCode,
                'oxarticles__oxprice' => $price,
                'oxarticles__oxstock' => 1,
                'oxarticles__oxactive' => 1,
                'oxarticles__oxissearch' => 0,
                'oxarticles__oxhidden' => 1,
                'oxarticles__oxartnum' => 'K3C_' . $configurationCode,
            ]);
            $article->setArticleLongDesc($description);

            // Configurators without a screenshot send no image
            if (!empty($configuration->image)) {
                $local_image_path = $this->downloadAndSaveConfigurationImage($configuration->image, strtolower('K3C_' . $configurationCode));
                $article->oxarticles__oxpic1 = basename($local_image_path);
            }

            if ($article->save()) {
                $basketItem = $basket->addToBasket($article->oxarticles__oxid->value, 1, null, $persParam);
                $this->setBasketItemPrice($basket, $basketItem, $price);
            }
        } else {
            $basketArticles = $configurationModel->getBasketProducts();
            foreach ($basketArticles as $basketArticle) {
                $articlePersParam = $basketArticle['params']['mainProduct'] ? $persParam : null;
                $basketItem = $basket->addToBasket($basketArticle['id'], $basketArticle['amount'], null, $articlePersParam);
                $this->setBasketItemPrice($basket, $basketItem, $basketArticle['price']);
            }
        }
    }

    /**
     * What the basket position shows of the configuration
     *
     * OXID prints persparams unescaped in its own templates (admin order view,
     * both order mails, basket), and the labels come from the public cfg/save.
     * So only readable text is stored, with every character that could start
     * HTML or leave an attribute replaced, see toText(). The full configuration
     * stays in K3 and can be loaded by its code.
     *
     * @param object $configuration
     * @return array<string, string>
     */
    protected function getPersParams($configuration): array
    {
        $params = [
            'K3-Code' => $this->toText((string)($configuration->code ?? '')),
        ];
        $selections = [];
        foreach ($this->flattenVariables($configuration->variables ?? []) as $variable) {
            if (!empty($variable->hidden)) {
                continue;
            }
            $label = $this->toText((string)($variable->variable->label ?? ''));
            $value = $this->toText($this->getVariableValue($variable));
            if ($label !== '' && $value !== '') {
                $selections[] = $label . ': ' . $value;
            }
        }
        if ($selections) {
            $params['Konfiguration'] = mb_substr(implode(', ', $selections), 0, 2000);
        }
        return $params;
    }

    /**
     * All variables, nested component variables included
     *
     * @param array $variables
     * @return array
     */
    protected function flattenVariables(array $variables): array
    {
        $result = [];
        foreach ($variables as $variable) {
            if (!is_object($variable)) {
                continue;
            }
            $result[] = $variable;
            if (!empty($variable->variables) && is_array($variable->variables)) {
                array_push($result, ...$this->flattenVariables($variable->variables));
            }
        }
        return $result;
    }

    /**
     * The value as the customer saw it: option label, else the entered value
     *
     * @param object $variable
     * @return string
     */
    protected function getVariableValue($variable): string
    {
        $selected = $variable->selected ?? null;
        $value = $variable->value ?? null;

        // Multiple choice: selected and value are lists
        if (is_array($selected)) {
            $labels = [];
            foreach ($selected as $i => $option) {
                $labels[] = (string)($option->label ?? (is_array($value) ? ($value[$i] ?? '') : ''));
            }
            return implode(', ', array_filter($labels, 'strlen'));
        }
        if (is_object($selected) && isset($selected->label) && $selected->label !== '') {
            return (string)$selected->label;
        }
        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }
        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * Plain text that is safe with and without HTML escaping
     *
     * Escaping alone does not work here: some OXID templates escape and would
     * show "&amp;", and the basket form posts persparams back from hidden
     * inputs, where the browser decodes entities again. Replacing the few
     * characters that matter keeps the text readable everywhere.
     *
     * @param string $text
     * @return string
     */
    protected function toText(string $text): string
    {
        $text = str_replace(['<', '>', '"', "'", '`'], ['‹', '›', '”', '’', '’'], $text);
        // A character reference (&#60; &lt; &#x3C; …) would become markup again
        // wherever a browser decodes it, e.g. in a basket input. Its ampersand
        // gets a lookalike; a plain "Schwarz & Weiß" stays as it is
        $text = preg_replace('/&(?=#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*;?)/iu', '＆', $text) ?? '';
        $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text) ?? '';
        return trim(preg_replace('/\s{2,}/u', ' ', $text) ?? '');
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
