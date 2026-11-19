<?php

namespace ObjectCode\K3\Core\Service;

use ObjectCode\K3\Application\Model\Export\Price;
use ObjectCode\K3\Application\Model\Export\Product;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

class Export
{
    /** Shop ID
     *
     */
    protected int $shopId = 1;

    /**
     * Language
     *
     * @var null
     */
    protected int $langId = 0;

    /**
     * Currency id
     *
     * @var null
     */
    protected int $currencyId = 0;

    /**
     * Currency object
     *
     * @var mixed
     */
    protected $currency = null;

    /**
     * Export data
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Returns attribute ident to identify k3 products
     *
     * @var string
     */
    protected string $attributeOxid = 'k3product';

    /**
     * Article filters from K3 ("Filter für Artikel"), filter name => values
     *
     * @var array<string, string[]>
     */
    protected array $filters = [];

    /**
     * Query parameters for the filtered products query
     *
     * @var array<string, string>
     */
    protected array $filterParams = [];

    /**
     * set default values
     */
    public function __construct()
    {
        $this->setLangId(Registry::getLang()->getBaseLanguage());
        $this->setShopId(Registry::getConfig()->getShopId());
        $this->setCurrencyId(Registry::getConfig()->getShopCurrency());
    }

    /**
     * Set shop id
     *
     * @param int $shopId
     * @return void
     */
    public function setShopId(int $shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * Return shop id
     *
     * @return int
     */
    public function getShopId(): int
    {
        return $this->shopId;
    }

    /**
     * Set lang id
     *
     * @param int $langId
     * @return void
     */
    public function setLangId(int $langId)
    {
        $this->langId = $langId;
    }

    /**
     * Set currency id
     *
     * @param string $currencyId
     * @return void
     */
    public function setCurrencyId(string $currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * Return currency id
     *
     * @return string
     */
    protected function getCurrencyId(): string
    {
        return $this->currencyId;
    }

    /**
     * Return currency
     *
     * @return
     */
    protected function getCurrency()
    {
        if ($this->currency === null) {
            $id = $this->getCurrencyId();
            $currencies = Registry::getConfig()->getCurrencyArray();
            if (!isset($currencies[$id])) {
                $this->currency = reset($currencies); // reset() returns the first element
            } else {
                $this->currency = $currencies[$id];
            }
        }
        return $this->currency;
    }

    /**
     * Return article table
     *
     * @param bool $core
     * @return string
     */
    public function getArticleTable(bool $core = false): string
    {
        if ($core) {
            return 'oxarticles';
        }
        $viewNameGenerator = Registry::get(\OxidEsales\Eshop\Core\TableViewNameGenerator::class);
        return $viewNameGenerator->getViewName('oxarticles', $this->getLangId(), $this->getShopId());
    }

    /**
     * Return oxobject2attribute table
     *
     * @param bool $core
     * @return string
     */
    public function getOxObject2AttributeTable(bool $core = false): string
    {
        if ($core) {
            return 'oxobject2attribute';
        }
        $viewNameGenerator = Registry::get(\OxidEsales\Eshop\Core\TableViewNameGenerator::class);
        return $viewNameGenerator->getViewName('oxobject2attribute', $this->getLangId(), $this->getShopId());
    }

    /**
     * Return lang id
     *
     * @return int
     */
    public function getLangId(): int
    {
        return $this->langId;
    }

    /**
     * Return export data
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function getProducts(): array
    {
        $products = $this->getExportProducts();
        foreach ($products as $product) {
            $this->data[] = $product->getArray();
        }
        return $this->data;
    }

    /**
     * Return export products
     *
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function getExportProducts(): array
    {
        $products = [];
        $query = $this->getProductsQuery();
        $result = DatabaseProvider::getDb()->getAll($query, $this->filterParams);
        if ($result && is_array($result) && count($result) > 0) {
            foreach ($result as $row) {
                $id = $row[0];
                $parentId = $row[1];
                $varCount = $row[2];
                if (!$parentId && $varCount > 0) {
                    $products = $this->getExportProductVariants($products, $id);
                } elseif (!in_array($id, array_keys($products))) {
                    if ( ($product = $this->getExportProduct($id)) ) {
                        $products[$id] = $product;
                    }
                }
            }
        }
        // Articles the plugin created for combined orders are K3's own and
        // must not come back into the configurator's article list
        $products = array_filter($products, fn($product) => stripos($product->getNo(), 'K3C_') !== 0);
        // Variants have article numbers of their own, so the reference filter
        // is checked on what is actually exported
        if (isset($this->filters['reference'])) {
            $products = array_filter($products, fn($product) => $this->matchesReference($product->getNo()));
        }
        return $products;
    }

    /**
     * Return export variants
     *
     * @param $products
     * @param $parentId
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function getExportProductVariants($products, $parentId): array
    {
        $variants = $this->getVariants($parentId);
        if (count($variants) > 0) {
            foreach ($variants as $variantId) {
                if (!in_array($variantId, array_keys($products))) {
                    if ( ($product = $this->getExportProduct($variantId)) ) {
                        $products[$variantId] = $product;
                    }
                }
            }
        }
        return $products;
    }

    /**
     * Return variants
     *
     * @param $id
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function getVariants($id): array
    {
        $table = $this->getArticleTable();
        $query = "select oxid from $table where oxparentid = :oxparentid and oxactive = 1";
        $rs = DatabaseProvider::getDb()->getCol($query, [
            ':oxparentid' => $id
        ]);
        if ($rs && is_array($rs) && count($rs) > 0) {
            return $rs;
        }
        return [];
    }

    /**
     * Return export product
     *
     * @param $id
     * @return Export|mixed|void
     */
    protected function getExportProduct($id)
    {
        $article = oxNew(Article::class);
        if ($article->loadInLang($this->getLangId(), $id) && !$article->isNotBuyable()) {
            $exportProduct = oxNew(Product::class);
            $exportProduct->setNo($article->getFieldData('oxartnum'));
            $exportProduct->setName($article->getFieldData('oxtitle'));
            $exportProduct->setDescription($article->getLongDesc());
            $category = $article->getCategory();
            $exportProduct->setCategory($category->getFieldData('oxtitle'));
            $prices = $this->getExportProductPrices($article);
            $exportProduct->setPrices($prices);
            return $exportProduct;
        }
    }

    /**
     * Return product prices
     *
     * @param $article
     * @return array
     */
    protected function getExportProductPrices($article): array
    {
        $prices = [];
        $prices[] = $this->getExportProductPrice($article);
        $exportTPrice = $this->getExportProductTPrice($article);
        if ($exportTPrice) {
            $prices[] = $exportTPrice;
        }
        $amountPrices = $this->getExportProductAmountPrices($article);
        if ($amountPrices && count($amountPrices) > 0) {
            foreach ($amountPrices as $amountPrice) {
                $prices[] = $amountPrice;
            }
        }
        return $prices;
    }

    /**
     * Return product price
     *
     * @param $article
     * @return Price|mixed
     */
    protected function getExportProductPrice($article)
    {
        $price = $article->getPrice();
        $exportPrice = oxNew(Price::class);
        $exportPrice->setPrice($price->getBruttoPrice());
        $exportPrice->setType('fixed');
        $currency = $this->getCurrency();
        $exportPrice->setCurrency($currency->name);
        return $exportPrice;
    }

    /**
     * Return strike through price
     *
     * @param $article
     * @return Price|mixed|void
     */
    protected function getExportProductTPrice($article)
    {
        $price = $article->getTPrice();
        if ($price) {
            $exportPrice = oxNew(Price::class);
            $exportPrice->setPrice($price->getBruttoPrice());
            $exportPrice->setType('fixed');
            $exportPrice->setDisplayOnly('streich');
            $currency = $this->getCurrency();
            $exportPrice->setCurrency($currency->name);
            return $exportPrice;
        }
    }

    /**
     * Return amount prices
     *
     * @param $article
     * @return array
     */
    protected function getExportProductAmountPrices($article): array
    {
        $amountPrices = $article->loadAmountPriceInfo();
        if ($amountPrices) {
            $exportPrices = [];
            $currency = $this->getCurrency();
            foreach ($amountPrices as $amountPrice) {
                $price = str_replace('.', '', $amountPrice->fbrutprice);
                $price = str_replace(',', '.', $price);
                $exportPrice = oxNew(Price::class);
                $exportPrice->setPrice($price);
                $exportPrice->setType('fixed');
                $exportPrice->setFromQty($amountPrice->getFieldData('oxamount'));
                $exportPrice->setToQty($amountPrice->getFieldData('oxamountto'));
                $exportPrice->setCurrency($currency->name);
                $exportPrices[] = $exportPrice;
            }
            return $exportPrices;
        }
        return [];
    }

    /**
     * Return product query
     *
     * @return string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function getProductsQuery(): string
    {
        // A filter from K3 replaces the K3 attribute; the attribute only
        // decides when no filter is set
        if (!empty($this->filters)) {
            return $this->getFilteredProductsQuery();
        }

        $o2aTable = $this->getOxObject2AttributeTable();
        $query = "select count(oxid) from $o2aTable where $o2aTable.oxattrid = '{$this->attributeOxid}' limit 1";
        $selectedCount = DatabaseProvider::getDb()->getOne($query);
        if ($selectedCount && $selectedCount > 0) {
            return $this->getSelectedProductsQuery();
        }
        return $this->getAllProductsQuery();
    }

    /**
     * Returns query to get all products
     *
     * @return string
     */
    protected function getAllProductsQuery(): string
    {
        $table = $this->getArticleTable();
        return "select $table.oxid, $table.oxparentid, $table.oxvarcount from $table where oxactive = 1 order by $table.oxartnum";
    }

    /**
     * Returns query to get all selected products
     *
     * @return string
     */
    protected function getSelectedProductsQuery(): string
    {
        $table = $this->getArticleTable();
        $o2aTable = $this->getOxObject2AttributeTable();
        return "select $table.oxid, $table.oxparentid, $table.oxvarcount from $table join $o2aTable on $o2aTable.oxobjectid = $table.oxid and $o2aTable.oxattrid = '{$this->attributeOxid}' where $table.oxactive = 1 order by $table.oxartnum";
    }


    /**
     * Set the article filters from K3
     *
     * @param array<string, string[]> $filters see \ObjectCode\K3\Core\ArticleFilter
     * @return void
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Query for all active articles matching the filters
     *
     * name, category and manufacturer are checked on the article row. A parent
     * article that matches brings all its variants, since variants usually
     * inherit title, categories and manufacturer from it.
     *
     * @return string
     */
    protected function getFilteredProductsQuery(): string
    {
        $table = $this->getArticleTable();
        $categoryTable = $this->getViewTable('oxcategories');
        $manufacturerTable = $this->getViewTable('oxmanufacturers');
        $where = ["$table.oxactive = 1"];
        $this->filterParams = [];

        if (isset($this->filters['name'])) {
            $where[] = $this->orConditions($this->filters['name'], fn($value) =>
                "$table.oxtitle LIKE " . $this->param('%' . $this->escapeLike($value) . '%'));
        }
        if (isset($this->filters['category'])) {
            $where[] = "EXISTS (SELECT 1 FROM oxobject2category o2c
                JOIN $categoryTable cat ON cat.oxid = o2c.oxcatnid
                WHERE o2c.oxobjectid = $table.oxid AND " .
                $this->orConditions($this->filters['category'], fn($value) =>
                    '(cat.oxid = ' . $this->param($value) . ' OR cat.oxtitle = ' . $this->param($value) . ')') . ')';
        }
        if (isset($this->filters['reference'])) {
            // Narrow down in SQL so only candidates get loaded; an article
            // matches by its own number or by one of its variants. The exact
            // check on the exported numbers follows in getExportProducts()
            $where[] = $this->orConditions($this->filters['reference'], function ($value) use ($table) {
                $like = $this->param(str_replace('*', '%', $this->escapeLike($value)));
                return "($table.oxartnum LIKE $like OR EXISTS (SELECT 1 FROM $table var
                    WHERE var.oxparentid = $table.oxid AND var.oxartnum LIKE $like))";
            });
        }
        if (isset($this->filters['manufacturer'])) {
            $where[] = "$table.oxmanufacturerid IN (SELECT man.oxid FROM $manufacturerTable man WHERE " .
                $this->orConditions($this->filters['manufacturer'], fn($value) =>
                    '(man.oxid = ' . $this->param($value) . ' OR man.oxtitle = ' . $this->param($value) . ')') . ')';
        }

        return "select $table.oxid, $table.oxparentid, $table.oxvarcount from $table where " .
            implode(' AND ', $where) . " order by $table.oxartnum";
    }

    /**
     * Does an article number match the reference filter (* as wildcard)?
     *
     * @param string $articleNumber
     * @return bool
     */
    protected function matchesReference(string $articleNumber): bool
    {
        foreach ($this->filters['reference'] ?? [] as $pattern) {
            $regex = '/^' . str_replace('\\*', '.*', preg_quote($pattern, '/')) . '$/iu';
            if (preg_match($regex, $articleNumber)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Language/shop view of a table
     *
     * @param string $table
     * @return string
     */
    protected function getViewTable(string $table): string
    {
        $viewNameGenerator = Registry::get(\OxidEsales\Eshop\Core\TableViewNameGenerator::class);
        return $viewNameGenerator->getViewName($table, $this->getLangId(), $this->getShopId());
    }

    /**
     * Add a bound parameter, return its placeholder
     *
     * @param string $value
     * @return string
     */
    protected function param(string $value): string
    {
        $name = ':f' . count($this->filterParams);
        $this->filterParams[$name] = $value;
        return $name;
    }

    /**
     * @param string[] $values
     * @param callable $toCondition
     * @return string
     */
    protected function orConditions(array $values, callable $toCondition): string
    {
        return '(' . implode(' OR ', array_map($toCondition, $values)) . ')';
    }

    /**
     * LIKE wildcards in a value are literals
     *
     * @param string $value
     * @return string
     */
    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
